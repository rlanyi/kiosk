#!/usr/bin/php -q
<?php

require_once __DIR__.'/vendor/autoload.php';
//error_reporting(E_STRICT);

$daemon = new Kiosk\Daemon();

// Scan command line attributes for allowed arguments
foreach ($argv as $k=>$arg) {
  if (substr($arg, 0, 2) == '--') {
    $daemon->setRunMode(substr($arg, 2));
  }
}

$daemon->run();
