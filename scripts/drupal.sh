#!/usr/bin/env php
<?php
// $Id: drupal.sh,v 1.9 2009/09/19 10:38:47 dries Exp $

/**
 * Drupal shell execution script
 *
 * Check for your PHP interpreter - on Windows you'll probably have to
 * replace line 1 with
 *   #!c:/program files/php/php.exe
 *
 * @param path  Drupal's absolute root directory in local file system (optional).
 * @param URI   A URI to execute, including HTTP protocol prefix.
 */
$script = basename(array_shift($_SERVER['argv']));

if (in_array('--help', $_SERVER['argv']) || empty($_SERVER['argv'])) {
  echo <<<EOF

Execute a Drupal page from the shell.

Usage:        {$script} [OPTIONS] "<URI>"
Example:      {$script} "http://mysite.org/node"

All arguments are long options.

  --help      This page.

  --root      Set the working directory for the script to the specified path.
              To execute Drupal this has to be the root directory of your
              Drupal installation, f.e. /home/www/foo/drupal (assuming Drupal
              running on Unix). Current directory is not required.
              Use surrounding quotation marks on Windows.

  --verbose   This option displays the options as they are set, but will
              produce errors from setting the session.

  URI         The URI to execute, i.e. http://default/foo/bar for executing
              the path '/foo/bar' in your site 'default'. URI has to be
              enclosed by quotation marks if there are ampersands in it
              (f.e. index.php?q=node&foo=bar). Prefix 'http://' is required,
              and the domain must exist in Drupal's sites-directory.

              If the given path and file exists it will be executed directly,
              i.e. if URI is set to http://default/bar/foo.php
              and bar/foo.php exists, this script will be executed without
              bootstrapping Drupal. To execute Drupal's cron.php, specify
              http://default/cron.php as the URI.


To run this script without --root argument invoke it from the root directory
of your Drupal installation with

  ./scripts/{$script}
\n
EOF;
  exit;
}

// define default settings
$cmd = 'index.php';
$_SERVER['HTTP_HOST']       = 'default';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['SERVER_SOFTWARE'] = NULL;
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['QUERY_STRING']    = '';
$_SERVER['PHP_SELF']        = $_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_USER_AGENT'] = 'console';

// toggle verbose mode
if (in_array('--verbose', $_SERVER['argv'])) {
  $_verbose_mode = true;
}
else {
  $_verbose_mode = false;
}

// parse invocation arguments
while ($param = array_shift($_SERVER['argv'])) {
  switch ($param) {
    case '--root':
      // change working directory
      $path = array_shift($_SERVER['argv']);
      if (is_dir($path)) {
        chdir($path);
        if ($_verbose_mode) {
          echo "cwd changed to: {$path}\n";
        }
      }
      else {
        echo "\nERROR: {$path} not found.\n\n";
      }
      break;

    default:
      if (substr($param, 0, 2) == '--') {
        // ignore unknown options
        break;
      }
      else {
        // parse the URI
        $path = parse_url($param);

        // set site name
        if (isset($path['host'])) {
          $_SERVER['HTTP_HOST'] = $path['host'];
        }

        // set query string
        if (isset($path['query'])) {
          $_SERVER['QUERY_STRING'] = $path['query'];
          parse_str($path['query'], $_GET);
          $_REQUEST = $_GET;
        }

        // set file to execute or Drupal path (clean urls enabled)
        if (isset($path['path']) && file_exists(substr($path['path'], 1))) {
          $_SERVER['PHP_SELF'] = $_SERVER['REQUEST_URI'] = $path['path'];
          $cmd = substr($path['path'], 1);
        }
        elseif (isset($path['path'])) {
          if (!isset($_GET['q'])) {
            $_REQUEST['q'] = $_GET['q'] = $path['path'];
          }
        }

        // display setup in verbose mode
        if ($_verbose_mode) {
          echo "Hostname set to: {$_SERVER['HTTP_HOST']}\n";
          echo "Script name set to: {$cmd}\n";
          echo "Path set to: {$_GET['q']}\n";
        }
      }
      break;
  }
}

if (file_exists($cmd)) {
  include $cmd;
}
else {
  echo "\nERROR: {$cmd} not found.\n\n";
}
exit();
