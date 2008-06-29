<?php
// $Id$

/**
 * @file
 * This script runs Drupal tests from command line.
 * You can provide groups or class names of the tests you wish to run.
 * For example: php scripts/run-functional-tests.sh Profile
 * If no arguments are provided, the help text will print.
 */

$test_names = array();
$host = 'localhost';
$path = '';
$script = basename(array_shift($_SERVER['argv']));
// XXX: is there a way to get the interpreter path dynamically?
$php = "/usr/bin/php";

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

  --concurrency [num]

              Run tests in parallel, up to [num] tests at a time.
              This is not supported under Windows.

  --all       Run all available tests.

  --class     Run tests identified by specific class names, instead of group names.

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
$concurrency = 1;
$class_names = FALSE;
$test_names = array();
$execute_batch = FALSE;
$test_id = NULL;

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
    case '--concurrency':
      $concurrency = array_shift($_SERVER['argv']);
      break;
    case '--test-id':
      $test_id = array_shift($_SERVER['argv']);
      break;
    case '--execute-batch':
      $execute_batch = TRUE;
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

if ($execute_batch) {
  if (is_null($test_id)) {
    echo "ERROR: --execute-batch should not be called interactively.\n";
    exit;
  }
  if ($concurrency == 1 || !function_exists('pcntl_fork')) {
    // Fallback to mono-threaded execution
    if (count($test_names) > 1) {
      foreach($test_names as $test_class) {
        // Note: we still need to execute each test in its separate Drupal environment
        passthru($php . " ./scripts/run-tests.sh --url $url --concurrency 1 --test-id $test_id --execute-batch $test_class");
      }
      exit;
    }
    else {
      // Execute an individual test
      $test_class = array_shift($test_names);
      drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
      simpletest_run_one_test($test_id, $test_class);
      exit;
    }
  }
  else {
    // Multi-threaded execution
    $children = array();
    while (!empty($test_names) || !empty($children)) {
      // Fork children
      // Note: we can safely fork here, because Drupal is not bootstrapped yet
      while(count($children) < $concurrency) {
        if (empty($test_names)) break;

        $child = array();
        $child['test_class'] = $test_class = array_shift($test_names);
        $child['pid'] = pcntl_fork();
        if (!$child['pid']) {
          // This is the child process, bootstrap and execute the test
          drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
          simpletest_run_one_test($test_id, $test_class);
          exit;
        }
        else {
          // Register our new child
          $children[] = $child;
        }
      }

      // Wait for children every 200ms
      usleep(200000);

      // Check if some children finished
      foreach($children as $cid => $child) {
        if (pcntl_waitpid($child['pid'], $status, WUNTRACED | WNOHANG)) {
          // This particular child exited
          unset($children[$cid]);
        }
      }
    }
    exit;
  }
}

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
    echo(" - " . $text . "\n");
  }
  exit;
}

// Run tests as user #1.
$GLOBALS['user'] = user_load(1);

// Load simpletest files
$all_tests = simpletest_get_all_tests();
$groups = simpletest_categorize_tests($all_tests);
$test_list = array();

if ($list) {
  // Display all availabe tests.
  echo("\nAvailable test groups\n---------------------\n\n");
  foreach ($groups as $group => $tests) {
    echo($group . "\n");
    foreach ($tests as $class_name => $instance) {
      $info = $instance->getInfo();
      echo " - " . $info['name'] . ' (' . $class_name . ')' . "\n";
    }
  }
  exit;
}

if ($all) {
  $test_list = array_keys($all_tests);
}
else {
  if ($class_names) {
    // Use only valid class names
    foreach ($test_names as $class_name) {
      if (isset($all_tests[$class_name])) {
        $test_list[] = $class_name;
      }
    }
  }
  else {
    // Resolve group names
    foreach ($test_names as $group_name) {
      if (isset($groups[$group_name])) {
        foreach($groups[$group_name] as $class_name => $instance) {
          $test_list[] = $class_name;
        }
      }
    }
  }
}

if (empty($test_list) && !$all) {
  echo("ERROR: No valid tests were specified.\n");
  exit;
}

// If not in 'safe mode', increase the maximum execution time:
if (!ini_get('safe_mode')) {
  set_time_limit(0);
}

echo "\n";
echo "Drupal test run\n";
echo "---------------\n";
echo "\n";

// Tell the user about what tests are to be run.
if ($all) {
  echo "All tests will run.\n\n";
}
else {
  echo "Tests to be run:\n";
  foreach ($test_list as $class_name) {
    $info = $all_tests[$class_name]->getInfo();
    echo " - " . $info['name'] . ' (' . $class_name . ')' . "\n";
  }
  echo "\n";
}

echo "Test run started: " . format_date(time(), 'long') . "\n";
echo "\n";

db_query('INSERT INTO {simpletest_test_id} VALUES (default)');
$test_id = db_last_insert_id('simpletest_test_id', 'test_id');

echo "Test summary:\n";
echo "-------------\n";
echo "\n";

// Now execute tests
passthru($php . " ./scripts/run-tests.sh --url $url --test-id $test_id --concurrency $concurrency --execute-batch " . implode(",", $test_list));

echo "\n";
echo "Test run ended: " . format_date(time(), 'long') . "\n";
echo "\n";

// Report results
echo "Detailed test results:\n";
echo "----------------------\n";
echo "\n";

$results_map = array(
  'pass' => 'Pass',
  'fail' => 'Fail',
  'exception' => 'Exception'
);

$results = db_query("SELECT * FROM {simpletest} WHERE test_id = %d ORDER BY test_class, message_id", $test_id);
while($result = db_fetch_object($results)) {
  if (isset($results_map[$result->status])) {
    $data = array(
      '[' . $results_map[$result->status] . ']',
      $result->message,
      $result->message_group,
      basename($result->file),
      $result->line,
      $result->caller,
    );
    echo implode("\t", $data) . "\n";
  }
}

// Cleanup our test results
db_query("DELETE FROM {simpletest} WHERE test_id = %d", $test_id);

// Support function:

/**
 * Run a single test (assume a Drupal bootstrapped environnement).
 */
function simpletest_run_one_test($test_id, $test_class) {
  simpletest_get_all_tests();
  $test = new $test_class($test_id);
  $test->run();
  $info = $test->getInfo();
  echo $info['name'] . ' ' . _simpletest_format_summary_line($test->_results) . "\n";
}

