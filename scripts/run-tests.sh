#!/usr/bin/php
<?php
// $Id$

/**
 * @file
 * This script runs Drupal tests from command line.
 * You can provide groups or class names of the tests you wish to run.
 * For example: php scripts/run-functional-tests.sh Profile
 * If no arguments are provided, the help text will print.
 */

$reporter = 'text';
$test_names = array();
$host = 'localhost';
$path = '';
$script = basename(array_shift($_SERVER['argv']));

if (in_array('--help', $_SERVER['argv']) || empty($_SERVER['argv'])) {
  echo <<<EOF

Run Drupal tests from the shell.

Usage:        {$script} [OPTIONS] <tests>
Example:      {$script} Profile

All arguments are long options.

  --help      Print this page.

  --list      Display all available test groups.

  --clean     Cleans up database tables or directories from previous, failed,
              tests and then exits (no tests are run).

  --url       Immediately preceeds a URL to set the host and path. You will
              need this parameter if Drupal is in a subdirectory on your
              localhost and you have not set \$base_url in settings.php.

  --reporter  Immediatly preceeds the name of the output reporter to use.  This
              Defaults to "text", while other options include "xml" and "html".

  --all       Run all available tests.

  --class     Run tests identified by speficic class names.

  <test1>[,<test2>[,<test3> ...]]

              One or more tests to be run.  By default, these are interpreted
              as the names of test groups as shown at ?q=admin/build/testing.
              These group names typically correspond to module names like "User"
              or "Profile" or "System", but there is also a group "XML-RPC".
              If --class is specified then these are interpreted as the names of
              specific test classes whose test methods will be run.  Tests must
              be separated by commas.  Ignored if --all is specified.

To run this script you will normally invoke it from the root directory of your
Drupal installation as

php  ./scripts/{$script}
\n
EOF;
  exit;
}

$list = FALSE;
$clean = FALSE;
$all = FALSE;
$class_names = FALSE;
$test_names = array();

while ($param = array_shift($_SERVER['argv'])) {
  switch ($param) {
    case '--list':
      $list = TRUE;
      break;
    case '--url':
      $url = array_shift($_SERVER['argv']);
      $parsed = parse_url($url);
      $host = $parsed['host'];
      $path = $parsed['path'];
      break;
    case '--all':
      $all = TRUE;
      break;
    case '--class':
      $class_names = TRUE;
      break;
    case '--clean':
      $clean = TRUE;
      break;
    case '--reporter':
      $reporter = array_shift($_SERVER['argv']);
      if (!in_array($reporter, array("text", "xml", "html"))) {
        $reporter = "text";
      }
      break;
    default:
      $test_names += explode(',', $param);
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

if (!module_exists('simpletest')) {
  echo("ERROR: The simpletest module must be enabled before this script can run.\n");
  exit;
}

if ($clean) {
  // Clean up left-over times and directories.
  simpletest_clean_environment();
  // Get the status messages and print them.
  $messages = array_pop(drupal_get_messages('status'));
  foreach($messages as $text) {
    echo("- " . $text . "\n");
  }
  exit;
}

// Run tests as user #1.
$GLOBALS['user'] = user_load(1);

//Load simpletest files
$total_test = &simpletest_get_total_test();

$test_instances = $total_test->getTestInstances();

if ($list) {
  // Display all availabe tests.
  echo("Available test groups:\n----------------------\n");
  foreach ($test_instances as $group_test) {
    echo($group_test->getLabel() . "\n");
  }
  exit;
}

if ($all) {
  $test_list = NULL;
}
else {
  if ($class_names) {
    $test_list = _run_tests_check_classes($test_names, $test_instances);
  }
  else {
    $test_list = _run_tests_find_classes($test_names, $test_instances);
  }
}
if (empty($test_list) && !$all) {
  echo("ERROR: No valid tests were specified.\n");
  exit;
}


// If not in 'safe mode', increase the maximum execution time:
if (!ini_get('safe_mode')) {
  set_time_limit(360);
}

// Tell the user about what tests are to be run.
if (!$all && $reporter == 'text') {
  echo("Tests to be run:\n");
  foreach ($test_list as $name) {
    echo("- " . $name . "\n");
  }
  echo("\n");
}

simpletest_run_tests(array_keys($test_list), $reporter);

// Utility functions:
/**
 * Check that each class name exists as a test, return the list of valid ones.
 */
function _run_tests_check_classes($test_names, $test_instances) {
  $test_list = array();
  $test_names = array_flip($test_names);

  foreach ($test_instances as $group_test) {
    $tests = $group_test->getTestInstances();
    foreach ($tests as $test) {
      $class = get_class($test);
      $info = $test->getInfo();
      if (isset($test_names[$class])) {
        $test_list[$class] = $info['name'];
      }
    }
  }
  return $test_list;
}

/**
 * Check that each group name exists, return the list of class in valid groups.
 */
function _run_tests_find_classes($test_names, &$test_instances) {
  $test_list = array();
  $test_names = array_flip($test_names);

  uasort($test_instances, 'simpletest_compare_instances');
  foreach ($test_instances as $group_test) {
    $group = $group_test->getLabel();
    if (isset($test_names[$group])) {
      $tests = $group_test->getTestInstances();
      foreach ($tests as $test) {
        $info = $test->getInfo();
        $test_list[get_class($test)] = $info['name'];
      }
    }
  }
  return $test_list;
}

