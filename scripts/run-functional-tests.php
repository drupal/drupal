#!/usr/bin/php
<?php
// $Id: run-functional-tests.php,v 1.1 2008/04/20 18:23:33 dries Exp $

/**
 * @file
 * This script can be run with browser or from command line.
 * You can provide class names of the tests you wish to run.
 * When this script is run from browser you can select which reporter to use html or xml.
 * For command line: php run_functional_tests.php SearchMatchTest,ProfileModuleTestSingle
 * For browser: http://yoursite.com/sites/all/modules/simpletest/run_all_tests.php?include=SearchMatchTest,ProfileModuleTestSingle&reporter=html
 * If none of these two options are provided all tests will be run.
 */

$tests = NULL;
$reporter = 'text';
$host = 'localhost';
$path = '';
array_shift($_SERVER['argv']); // throw away script name
while ($param = array_shift($_SERVER['argv'])) {
  switch ($param) {
    case '--url':
      $url = array_shift($_SERVER['argv']);
      $parse = parse_url($url);
      $host = $parse['host'];
      $path = $parse['path'];
      break;

    default:
      $tests = explode(',', $param);
      break;
  }
}

$_SERVER['HTTP_HOST'] = $host;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_SOFTWARE'] = 'Apache';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = $path .'/';
$_SERVER['SCRIPT_NAME'] = $path .'/index.php';
$_SERVER['PHP_SELF'] = $path .'/index.php';
$_SERVER['HTTP_USER_AGENT'] = 'Drupal command line';

chdir(realpath(dirname(__FILE__) . '/..'));
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

//load simpletest files
simpletest_load();

// If not in 'safe mode', increase the maximum execution time:
if (!ini_get('safe_mode')) {
  set_time_limit(360);
}

simpletest_run_tests($tests, $reporter);
?>
