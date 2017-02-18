<?php

namespace Kiosk;

use \System_Daemon;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PlaylistService {

  static protected $store = 'data/playlists.json';
  static protected $data = array();
  static protected $playlist = null;
  static protected $screen = null;
  static protected $screenownid = null;
  static protected $prevscreen = null;
  static protected $prevscreenownid = null;
  static protected $screen_start = null;
  static protected $loopc = 0;
	
	static protected $valid_image_extensions = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
	static protected $valid_video_extensions = array('avi', 'mp4', 'mpg', 'mpeg');

  protected function __construct() {

  }

  static public function init() {

    self::$data = json_decode(file_get_contents(__DIR__ . '/../../' . self::$store), true);
    self::$playlist = self::getPlaylist(true);
    self::$screen = self::$screen_start = null;

    return self::$playlist;
    
  }

  static public function getPlaylist($force_reload = false) {

    // There is only one actual playlist at a time. When a playlist is finished, the daemon will check if the playlist has to be changed.
    if (!self::isLoaded()) {
      return null;
    }

    if (!$force_reload) {
      return self::$playlist;
    }

    foreach (self::$data as $key => $playlist) {

      if (!$playlist['enabled']) {
				System_Daemon::debug(sprintf('Playlist %s is disabled, skipping', self::$data[$key]['name']));
        continue;
      }

      if ((($playlist['starttime'] > microtime(true)*1000) && ($playlist['starttime'] > 0)) || (($playlist['endtime'] < microtime(true)*1000) && ($playlist['endtime'] > 0))) {
				System_Daemon::debug(sprintf('Playlist %s is out of time frame, skipping', self::$data[$key]['name']));
        continue;
      }

      return self::$playlist = &self::$data[$key];

    }

    return null;

  }

  static public function checkScreen() {

    if (!count(self::$playlist['screens'])) {
      return false;
    }

    // If this is the first check, we need to start with the first screen
    if ((self::$screen === null) && (self::$screen_start === null)) {
      self::$screen = 0;
      self::$screenownid = 'id0';
      self::$screen_start = microtime(true)*1000;
      self::$loopc = 1;
      return true;
    }

    // Finished the last loop
    if ((self::$screen === null) && (self::$screen_start !== null)) {
      return false;
    }

    // We have to change screen after the given duration
    // TODO: auto duration?
    if ((self::$screen_start + self::$playlist['screens'][self::$screen]['duration']) < microtime(true)*1000) {

      self::$prevscreen = self::$screen;
      self::$prevscreenownid = self::$screenownid;
      self::$screen++;
      self::$screenownid = 'id' . self::$screen;

      if (self::$screen >= count(self::$playlist['screens'])) {

        // Rotating?
        if ((self::$playlist['loop'] === true) || (is_numeric(self::$playlist['loop']) && (self::$loopc < self::$playlist['loop']))) {
          self::$screen = 0;
          self::$loopc++;
        } else {
          // We have to stop
          self::$screen = null;
          return true;
        }

      }

      self::$screen_start = microtime(true)*1000;
      return true;
    }

    return false;
  }

  static public function isWritable() {

    if (!file_exists(__DIR__ . '/../../' . self::$store)) {
      file_put_contents(__DIR__ . '/../../' . self::$store, json_encode(array()));
    }
    return is_writable(__DIR__ . '/../../' . self::$store);

  }

  static public function savePlaylist() {

    if (!self::isLoaded()) {
      return false;
    }

    return file_put_contents(__DIR__ . '/../../' . self::$store, json_encode(self::$data));
  }

  static public function disablePlaylist($id, $enable = false) {

    if (!self::isLoaded()) {
      return false;
    }

    foreach (self::$data as &$playlist) {
      if ($id === true) {
        $playlist['enabled'] = $enable;
      }
      if ($id === $playlist['id']) {
        $playlist['enabled'] = $enable;
        return true;
      }
    }

    return ($id === true);
  }

  static public function enablePlaylist($id) {
    return self::disablePlaylist($id, true);
  }

  static public function getScreen() {

    if ((self::$playlist) && (self::$screen !== null)) {
      return self::$playlist['screens'][self::$screen];
    }

    return false;

  }

  static public function getScreenId() {

    if ((self::$playlist) && (self::$screen !== null)) {
      return self::$screen;
    }

  }

  static public function getPrevScreenId() {

    return self::$prevscreen;

  }

  static public function getScreenOwnId() {

    if ((self::$playlist) && (self::$screen !== null)) {
      return self::$screenownid;
    }

  }

  static public function getPrevScreenOwnId() {

    return self::$prevscreenownid;

  }

  static public function isLoaded() {

    return (count(self::$data) > 0);
    
  }

  static public function getData() {
    return self::$data;
  }
  
  /**
   * Search datadir for folders and generate new playlists. Videos will be shown as separate playlists. 
	 * Images will be added to the playlist named after the folder they reside in. 
   *
   * @return void
   * @author  
   */
  static public function refreshPlaylist($datadir) {
		if (!file_exists($datadir) || !is_dir($datadir)) {
			throw new Exception('Nincs ilyen könyvtár', 1);
		}
		
		$playlist = array();
		
		if (file_exists($datadir) && is_dir($datadir)) {
			$i = 1;
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($datadir)) as $file => $object)
			{
				$screen = null;
		    if (is_file($file) && count(preg_grep(sprintf("/%s/i", $object->getExtension()) , self::$valid_video_extensions))) {
		    	if (false !== ($movie = new \ffmpeg_movie($file)))
					{
						// This is a valid and supported video file (based on file extension and on the fact that ffmpeg could open it)
						
						$screen = array(
							'duration' => ceil(($duration = $movie->getDuration()) * 1000),
							'assets' => array(
								array(
									'type' => 'video',
									'name' => sprintf('%s (%s)', basename($file), ((($duration) > 3600) ? gmdate('H:i:s', $duration) : gmdate('i:s', $duration))),
									'filename' => $file
								),
							)
						);
					}
				}

		    if (is_file($file) && count(preg_grep(sprintf("/%s/i", $object->getExtension()) , array_keys(self::$valid_image_extensions)))) {
		    	if ((false !== ($d = getimagesize($file))) && (in_array($d['mime'], self::$valid_image_extensions)))
					{
						// This is a valid and supported image file (based on file extension and on the fact that getimagesize() could open it)
						
						$screen = array(			    	
			    		'duration' => 5000,
			    		'assets' => array(
			    			array(
					    		'type' => 'image',
					    		'dimensions' => 'auto',
									'name' => sprintf('%s', basename($file)),
					    		'filename' => $file
								),
							)		    		
						);
					}
				}

				if ((is_array($screen)) && ($screen['duration'] > 0))
				{
					// The playlist key is a hash from the directory which contains the file, so everything in the same directory will
					// belong to the same playlist 
		    	$key = md5(dirname($file));
						
					if (!array_key_exists(sprintf('playlist_%s', $key), $playlist)) {
						// This is a new playlist, set the default values
		        $playlist[sprintf('playlist_%s', $key)] = array(
							'id' => $key,
							'name' => substr(dirname($file), strlen($datadir)+1) . ' könyvtár: ',
							'starttime' => 0,
							'endtime' => 0,
							'priority' => 1000-$i++,
							'enabled' => false,
							'loop' => true, // True means loop forever. Zero (0) means don't loop. Any other integer value means the exact loop count.
							'screens' => array(
								$screen
							)
						);
					} else {
						// This playlist is already exists, add the new screen to it
						$playlist[sprintf('playlist_%s', $key)]['screens'][] = $screen;
//						$playlist[sprintf('playlist_%s', $key)]['name'] .= ', ' . $name;
					}
				}
			}
		}

		foreach ($playlist as $key => $pl)
		{
			// Sort playlist screens by filename (of the first asset)
			$playlist[$key]['screens'] = multiSort($pl['screens'], SORT_ASC, 'assets/0/filename');

			// Update the visible name of the playlists (now it's ordered correctly)
			$names = array();
			foreach ($playlist[$key]['screens'] as $screen)
			{
				$names[] = $screen['assets'][0]['name'];
			}
			$playlist[$key]['name'] .= implode(', ', $names); 
		}

		// Sort playlists by name
		$playlist = multiSort($playlist, SORT_ASC, 'name');

		self::$data = $playlist;
		self::savePlaylist();
		return true;	
  }
}
