<?php

  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../src/common.php';
  require_once __DIR__ . '/../config/config.php';
  ////

//  error_reporting(0);
	error_reporting(E_ALL ^ E_NOTICE);

  use Kiosk\PlaylistService;

  if (!PlaylistService::isWritable()) {
    h('error', 'Jogosultság hiba!');
  }

  switch ($_SERVER['REQUEST_URI']) {

    case '/':
      header('Location: /playlist');
    break;

    case '/playlist':

      PlaylistService::init();
      $pldata = array();
      foreach (PlaylistService::getData() as $pld) {
        $pldata[$pld['id']] = $pld['name'];
      }

      if ('POST' == $_SERVER['REQUEST_METHOD']) {

        $saved = false;
		  
				// Stop
        if (isset($_POST['stop'])) {
          PlaylistService::disablePlaylist(true);
          $saved = PlaylistService::savePlaylist();
        }

				// Play
        if (isset($_POST['play'])) {
          if (!isset($_POST['form']['playlist']) || !in_array($_POST['form']['playlist'], array_keys($pldata))) {
            h('error', 'Nem választottál ki semmit!');
          } else {
            PlaylistService::disablePlaylist(true);
            PlaylistService::enablePlaylist($_POST['form']['playlist']);
            $saved = PlaylistService::savePlaylist();
          }
        }

				// Refresh
				if (isset($_POST['refresh'])) {
					try {
						$saved = PlaylistService::refreshPlaylist($config['datadir']);
			      $pldata = array();
			      foreach (PlaylistService::getData() as $pld) {
			        $pldata[$pld['id']] = $pld['name'];
			      }
					}
					catch (Exception $e) {
						h('error', $e->getMessage());
					}
				}

        if ($saved) {
          $daemon = new Kiosk\Daemon();
          $daemon->reload();
          h('info', 'Változások mentve');
        }

      }

      $content = p('playlistform', array('playlist' => $pldata));

    break;
		
		case '/screen.jpg':
			$f = '/tmp/screen.jpg';
			$f2 = '/tmp/screen-thumb.jpg';
			exec(sprintf('DISPLAY=:0.0 scrot -t 20 %s', $f));	
			header('Content-Type: image/jpeg');
			header('Content-Length: ' . filesize($f2));
			if (file_exists($f2)) {
				readfile($f2);
				die();
			} else {
				header('Location: /404');
			}
		break;

    default:
      header("Status: 404 Not Found");
      header("HTTP/1.0 404 Not Found");
      $content = p('404');
    break;

  }


  ////
  echo p('main', array('content' => $content));
