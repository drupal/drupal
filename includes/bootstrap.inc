<?php

function conf_init() {

  /*
  ** Try finding a matching configuration file by stripping the website's
  ** URI from left to right.  If no configuration file is found, return a
  ** default value 'conf'.
  */

  $uri = $_SERVER["PHP_SELF"];

  $file = strtolower(strtr($_SERVER["HTTP_HOST"] . substr($uri, 0, strrpos($uri, "/")), "/:", ".."));

  while (strlen($file) > 4) {
    if (file_exists("includes/$file.php")) {
      return $file;
    }
    else {
      $file = substr($file, strpos($file, ".") + 1);
    }
  }

  return "conf";
}

function variable_init($conf = array()) {
  $result = db_query("SELECT * FROM {variable} ");
  while ($variable = db_fetch_object($result)) {
    if (!isset($conf[$variable->name])) {
      $conf[$variable->name] = unserialize($variable->value);
    }
  }

  return $conf;
}

function variable_get($name, $default) {
  global $conf;

  return isset($conf[$name]) ? $conf[$name] : $default;
}

function variable_set($name, $value) {
  global $conf;

  db_query("DELETE FROM {variable} WHERE name = '%s'", $name);
  db_query("INSERT INTO {variable} (name, value) VALUES ('%s', '%s')", $name, serialize($value));

  $conf[$name] = $value;
}

function variable_del($name) {
  global $conf;

  db_query("DELETE FROM {variable} WHERE name = '%s'", $name);

  unset($conf[$name]);
}

function cache_get($key) {
  $cache = db_fetch_object(db_query("SELECT data, created, headers FROM {cache} WHERE cid = '%s'", $key));
  return $cache->data ? $cache : 0;
}

function cache_set($cid, $data, $expire = 0, $headers = NULL) {
  db_query("UPDATE {cache} SET data = '%s', created = %d, expire = %d, headers = '%s' WHERE cid = '%s'", $data, time(), $expire, $headers, $cid);
  if (!db_affected_rows()) {
    db_query("INSERT INTO {cache} (cid, data, created, expire, headers) VALUES('%s', '%s', %d, %d, '%s')", $cid, $data, time(), $expire, $headers);
  }
}

function cache_clear_all($cid = NULL) {
  if (empty($cid)) {
    db_query("DELETE FROM {cache} WHERE expire <> 0");
  }
  else {
    db_query("DELETE FROM {cache} WHERE cid = '%s'", $cid);
  }
}

function page_set_cache() {
  global $user;

  if (!$user->uid && $_SERVER["REQUEST_METHOD"] == "GET") {
    if ($data = ob_get_contents()) {
      cache_set(request_uri(), $data, 1, drupal_get_headers());
    }
  }
}

function page_get_cache() {
  global $user;

  $cache = NULL;

  if (!$user->uid && $_SERVER["REQUEST_METHOD"] == "GET") {
    $cache = cache_get(request_uri());

    if (empty($cache)) {
      ob_start();
    }
  }

  return $cache;
}

function drupal_page_header() {
  if (variable_get("dev_timer", 0)) {
    timer_start();
  }

  if (variable_get("cache", 0)) {
    if ($cache = page_get_cache()) {

      // Set default values:
      $date = gmdate("D, d M Y H:i:s", $cache->created) ." GMT";
      $etag = '"'. md5($date) .'"';

      // Check http headers:
      $modified_since = isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) ? $_SERVER["HTTP_IF_MODIFIED_SINCE"] == $date : NULL;
      if (!empty($_SERVER["HTTP_IF_MODIFIED_SINCE"]) && ($timestamp = strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"])) != -1) {
        $modified_since = $cache->created <= $timestamp;
      }
      else {
        $modified_since = NULL;
      }
      $none_match = !empty($_SERVER["HTTP_IF_NONE_MATCH"]) ? $_SERVER["HTTP_IF_NONE_MATCH"] == $etag : NULL;

      // The type checking here is very important, be careful when changing entries.
      if (($modified_since !== NULL || $none_match !== NULL) && $modified_since !== false && $none_match !== false) {
        header("HTTP/1.0 304 Not Modified");
        exit();
      }

      // Send appropriate response:
      header("Last-Modified: $date");
      header("ETag: $etag");

      /*
      ** Send the original request's headers.  We send them one after
      ** another so PHP's header() function can deal with duplicate
      ** headers.
      */

      $headers = explode("\n", $cache->headers);
      foreach ($headers as $header) {
        header($header);
      }

      print $cache->data;

      /*
      ** call all init() and exit() hooks without including all modules
      ** only use those hooks for critical operations
      */
      foreach (bootstrap_hooks() as $hook) {
        module_invoke_all($hook);
      }
      exit();
    }
  }
}

// critical hooks called even when serving a cached page
function bootstrap_hooks() {
  return array('init', 'exit');
}

/*
** Unserializes and appends elements from a serialized string
** $obj is the object to which we shall append
** $field is the element whose value is a serialized string
*/
function drupal_unpack($obj, $field = 'data') {
  if ($obj->$field && $data = unserialize($obj->$field)) {
    foreach ($data as $key => $value) {
      if (!isset($obj->$key)) {
        $obj->$key = $value;
      }
    }
  }
  return $obj;
}

function referer_uri() {
  if (isset($_SERVER["HTTP_REFERER"])) {
    return check_url($_SERVER["HTTP_REFERER"]);
  }
}

function arg($index) {
  static $arguments, $q;

  if (empty($arguments) || $q != $_GET["q"]) {
    $arguments = explode("/", $_GET["q"]);
  }

  return $arguments[$index];
}

function check_query($text) {
  return addslashes($text);
}

function check_url($uri) {
  $uri = htmlspecialchars($uri, ENT_QUOTES);

  /*
  ** We replace ( and ) with their entity equivalents to prevent XSS
  ** attacks.
  */

  $uri = strtr($uri, array("(" => "&040;", ")" => "&041;"));

  return $uri;
}

function request_uri() {
  /*
  ** Since request_uri() is only available on Apache, we generate
  ** equivalent using other environment vars.
  */

  if (isset($_SERVER["REQUEST_URI"])) {
    $uri = $_SERVER["REQUEST_URI"];
  }
  else {
    if (isset($_SERVER["argv"])) {
      $uri = $_SERVER["PHP_SELF"] ."?". $_SERVER["argv"][0];
    }
    else {
      $uri = $_SERVER["PHP_SELF"] ."?". $_SERVER["QUERY_STRING"];
    }
  }

  return check_url($uri);
}

function timer_start() {
  global $timer;
  list($usec, $sec) = explode(" ", microtime());
  $timer = (float)$usec + (float)$sec;
}

function watchdog($type, $message, $link = NULL) {
  global $user;
  db_query("INSERT INTO {watchdog} (uid, type, message, link, location, hostname, timestamp) VALUES (%d, '%s', '%s', '%s', '%s', '%s', %d)", $user->uid, $type, $message, $link, request_uri(), $_SERVER['REMOTE_ADDR'], time());
}

unset($conf);
$config = conf_init();

include_once "includes/$config.php";
include_once "includes/database.inc";
include_once "includes/session.inc";
include_once "includes/module.inc";

// initialize configuration variables, using values from conf.php if available:
$conf = variable_init(isset($conf) ? $conf : array());
?>
