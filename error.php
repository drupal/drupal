<?php

include_once "includes/common.inc";

$errors = array(
  500 => "500 error: internal server error",
  404 => "404 error: '". $_SERVER["REDIRECT_URL"] ."' not found",
  403 => "403 error: access denied - forbidden",
  401 => "401 error: authorization required",
  400 => "400 error: bad request"
);

if ($errors[$_SERVER["REDIRECT_STATUS"]]) {
  watchdog("httpd", $errors[$_SERVER["REDIRECT_STATUS"]]);
  header("HTTP/1.0 ". $errors[$_SERVER["REDIRECT_STATUS"]]);
}

include_once("$base_url/index.php");
?>
