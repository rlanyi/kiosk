<?php

namespace Kiosk;

use \System_Daemon;

class Daemon {

  protected $runmode = array(
    'help' => false,
    'no-daemon' => false,
    'stop' => false,
    'reload' => false,
    'debug' => false,
  );
  private $runmodetxt = array(
    'help' => 'Show this help',
    'no-daemon' => 'Don\'t demonize, run in foreground',
    'stop' => 'Stop running daemon',
    'reload' => 'Reload playlist and start over',
    'debug' => 'Log everything',
  );
  protected $options = array(
    'appName' => 'kiosk',
    'appDir' => '.',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '10M',
    'appRunAsGID' => 1000,
    'appRunAsUID' => 1000,
    'logLocation' => './data/log/kiosk.log',
    'logVerbosity' => System_Daemon::LOG_NOTICE,
    'appPidLocation' => './data/run/kiosk/kiosk.pid',
    'usePEAR' => false,
  );
  static private $pids = array();

  public function __construct() {

    $this->options['logLocation'] = __DIR__ . '/../../' . $this->options['logLocation'];
    $this->options['appPidLocation'] = __DIR__ . '/../../' . $this->options['appPidLocation'];

    System_Daemon::setOptions($this->options);
    System_Daemon::setSigHandler(SIGUSR1, array('Kiosk\Daemon', 'handler'));
//    System_Daemon::setSigHandler(SIGUSR2, array('Kiosk\Daemon', 'handler'));

    PlaylistService::init();

  }

  private static function process() {

    if (PlaylistService::checkScreen()) {

      $playlist = PlaylistService::getPlaylist();

      if (!$screen = PlaylistService::getScreen()) {
        System_Daemon::notice('playlist %s ended', $playlist['name']);
        self::killScreen(PlaylistService::getPrevScreenOwnId());
				PlaylistService::disablePlaylist($playlist['id']);
				PlaylistService::savePlaylist();
        return true;
      }

      System_Daemon::notice('screen has been changed to %s/%d (%d assets)', $playlist['name'], PlaylistService::getScreenId(), count($screen['assets']));

      // TODO: foreach instead of [0]
      switch ($screen['assets'][0]['type']) {
        case 'clock':
          $pid = intval(shell_exec('xclock > /dev/null 2>&1 & echo $!'));
        break;

        case 'video':
          if (strpos($screen['assets'][0]['filename'], '/') === false) {
            $filename = sprintf('%s./data/assets/video/%s', __DIR__ . '/../../', $screen['assets'][0]['filename']);
          } else {
            $filename = $screen['assets'][0]['filename'];
          }

          if (file_exists('/usr/bin/omxplayer')) {
            $command = 'timeout %s omxplayer %s > /dev/null 2>&1 & echo $!';
            $type = 'kill';
          } elseif (file_exists('/usr/bin/mplayer')) {
            $command = 'timeout %s mplayer -fs %s > /dev/null 2>&1 & echo $!';
            $type = 'kill';
          } else {
            continue;
          }

					$command = sprintf($command, round($screen['duration'] / 1000), escapeshellarg($filename));
					System_Daemon::notice($command);
          $pid = intval(shell_exec($command));
        break;

        case 'image':
          if (strpos($screen['assets'][0]['filename'], '/') === false) {
            $filename = sprintf('%s./data/assets/image/%s', __DIR__ . '/../../', $screen['assets'][0]['filename']);
          } else {
            $filename = $screen['assets'][0]['filename'];
          }
					$command = sprintf('timeout %s feh --auto-zoom --borderless --fullscreen %s > /dev/null 2>&1 & echo $!', round($screen['duration'] / 1000) + 2, escapeshellarg($filename));

					System_Daemon::debug($command);
          $pid = intval(shell_exec($command));
          $type = 'kill';
        break;

        default:
          System_Daemon::warning('{appName} received unrecognized task: %s', $s);
        break;
      }

      if (isset($pid)) {
        self::$pids[PlaylistService::getScreenOwnId()] = array('type' => $type, 'pid' => $pid);
        System_Daemon::info('new process with PID %s has been started', $pid);
      }

    }

    return true;

  }

  public static function handler($signal) {

    System_Daemon::info('Reveived signal %s', $signal);
    if (($signal === SIGUSR1) && ($pid = self::getCurrentPid())) {

      if (file_exists(sprintf(__DIR__ . '/../../' . 'data/tmp/%s%s.%s', System_Daemon::opt('appName'), $pid, 'reload'))) {

        System_Daemon::notice('Control command received, attempting to reload playlist');
        self::killScreen(true);
        PlaylistService::init();

      }

      if (file_exists(sprintf(__DIR__ . '/../../' . 'data/tmp/%s%s.%s', System_Daemon::opt('appName'), $pid, 'shutdown'))) {

        System_Daemon::notice('Control command received, shutting down');
        self::killScreen(true);
        self::clearTemp();
        System_Daemon::stop();
      }

      self::clearTemp();
    }

  }

  public function run() {

    // Help mode. Shows allowed arguments and quit directly
    if ($this->runmode['help']) {
        echo 'Usage: '.$_SERVER['SCRIPT_FILENAME'].' [runmode]' . "\n";
        echo 'Available runmodes:' . "\n";
        foreach ($this->runmode as $runmod=>$val) {
            echo ' --'.$runmod;
            if (array_key_exists($runmod, $this->runmodetxt)) {
              echo str_repeat("\t", max(4 - (strlen($runmod) + 4) / 8, 2)) . $this->runmodetxt[$runmod];
            }
            echo "\n";
        }
        echo "\n" . 'Without runmode: start daemon' . "\n\n";
        die();
    }

    if ($this->runmode['debug']) {
      System_Daemon::opt('logVerbosity', System_Daemon::LOG_DEBUG);
    }


    if ($this->runmode['stop']) {
      System_Daemon::opt('logVerbosity', System_Daemon::LOG_DEBUG);
      $this->sendSignal('shutdown');
      die();
    }

    if ($this->runmode['reload']) {
      $this->reload();
      die();
    }
    
    // default
    $this->start();

  }

  public function setRunMode($runmode) {

    // Allowed arguments & their defaults
    if (!array_key_exists($runmode, $this->runmode)) {
      System_Daemon::err('invalid runmode, exiting...');
      die();
    }

    $this->runmode[$runmode] = true;

  }

  private static function killScreen($screen_id) {

    $pidlist = array();
    if ($screen_id === true) {

      $pidlist = self::$pids;
      self::$pids = array();

    } else {

      System_Daemon::debug(json_encode(self::$pids));
      if (isset(self::$pids[$screen_id]) && is_array(self::$pids[$screen_id])) {

        $pidlist = array(self::$pids[$screen_id]);
        unset(self::$pids[$screen_id]);

        // Sleeping a bit to avoid blinking (this can be a problem if we have shorter screens in the playlist)
        sleep(2);

      }
    }

    if (is_array($pidlist)) {

      System_Daemon::debug(json_encode($pidlist));
      foreach ($pidlist as $piddata) {

        $pid = $piddata['pid'];
        switch ($piddata['type']) {
          case 'kill':
            System_Daemon::debug(sprintf('kill %s > /dev/null 2>&1 &', $pid));
            shell_exec(sprintf('kill %s > /dev/null 2>&1 &', $pid));
          break;

          case 'pkill':
            shell_exec(sprintf('pkill -TERM -P %s > /dev/null 2>&1 &', $pid));
          break;
        }

        System_Daemon::debug('PID %s has been killed', $pid);

      }
    }
  }

  private function start() {

    $this->clearTemp();

    // This program can also be run in the forground with runmode --no-daemon
    if (!$this->runmode['no-daemon']) {
        // Spawn Daemon
        System_Daemon::start();
    }

    $cnt = 1;
    $runningOkay = true;

    while (!System_Daemon::isDying() && $runningOkay) {

        $mode = '"'.(System_Daemon::isInBackground() ? '' : 'non-' ). 'daemon" mode';

        System_Daemon::debug('{appName} running in %s %s', $mode, $cnt++);

        $runningOkay = self::process();

        if (!$runningOkay) {
            System_Daemon::err('process() produced an error, so this will be my last run');
        }
        System_Daemon::iterate(1);
			
    }

    System_Daemon::stop();

  }

  protected static function clearTemp() {

    $files = glob(__DIR__ . '/../../' . 'data/tmp/*');

    foreach ($files as $file) {

      if (is_file($file)) {
        unlink($file);
      }

    }
    
  }

  public static function getCurrentPid() {

    $appPidLocation = System_Daemon::opt('appPidLocation');

    if (file_exists($appPidLocation)) {
      return file_get_contents($appPidLocation);
    }
    return false;
  
  }

  protected function sendSignal($signal) {

    if (($pid = $this->getCurrentPid()) && (touch(sprintf(__DIR__ . '/../../' . 'data/tmp/%s%s.%s', System_Daemon::opt('appName'), $pid, $signal)))) {
      System_Daemon::info('Sending signal to %s', $pid);
      exec('kill -USR1 ' . $pid);
//      posix_kill($pid, SIGUSR1);
    } else {
      System_Daemon::err('Signal couldn\'t be sent');
    }

  }

  public function reload() {

    $this->sendSignal('reload');

  }

}
