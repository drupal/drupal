#!/Applications/MAMP/bin/php5/bin/php
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
$verbose = FALSE;
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
  die(t('Error: The simpletest module must be enabled before this script can run.') ."\n");
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

$tests = simpletest_get_all_tests();
$test_list = array();

if ($all) {
  $test_list = $tests;
}
else if ($class_names) {
  foreach ($test_names as $test) {
    if (isset($tests[$test])) {
      $test_list[$test] = $tests[$test];
    }
  }
}
else {
  $groups = simpletest_categorize_tests($tests);
  foreach ($test_names as $test) {
    if (isset($groups[$test])) {
      $test_list += $groups[$test];
    }
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
if (!$all) {
  echo("Tests to be run:\n");
  foreach ($test_list as $instance) {
    $info = $instance->getInfo();
    echo("- " . $info['name'] . "\n");
  }
  echo("\n");
}

db_query('INSERT INTO {simpletest_test_id} VALUES (default)');
$test_id = db_last_insert_id('simpletest_test_id', 'test_id');

$test_results = array('#pass' => 0, '#fail' => 0, '#exception' => 0);

foreach ($test_list as $class => $instance) {
  $instance = new $class($test_id);
  $instance->run();
  $info = $instance->getInfo();
  $test_results[$class] = $instance->_results;
  foreach ($test_results[$class] as $key => $value) {
    $test_results[$key] += $value;
  }
  echo(t('@name: @summary', array('@name' => $info['name'], '@summary' => _simpletest_format_summary_line($test_results[$class]))) . "\n");
}

echo(_simpletest_format_summary_line($test_results) . "\n");

