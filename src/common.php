<?php

  function p($template, $params = array()) {
  	GLOBAL $config;

    ob_start();
    require(__DIR__ . '/../views/' . $template . '.html.php');
    return ob_get_clean();

  }

  function h($level, $message) {

    $_SESSION['msg'][] = array('level' => $level, 'message' => $message);

  }

	function multiSort($data, $sortDirection, $field) {

    if(empty($data) || !is_array($data) || count($data) < 2) {
      return $data;
    }

    // Parse our search field path
    $parts = explode("/", $field);

    foreach ($data as $key => $row) {
      $temp = &$row;
      foreach($parts as $key2) {
        $temp = &$temp[$key2];
      }
      $orderBy[$key] = $temp;
  }
    unset($temp);

    array_multisort($orderBy, $sortDirection, $data);

    return $data;
	}
  