<?php

/**
 * @file
 * This script runs Drupal tests from command line.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Timer;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Database\Database;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Test\TestRunnerKernel;
use Drupal\simpletest\Form\SimpletestResultsForm;
use Drupal\simpletest\TestBase;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once __DIR__ . '/../../autoload.php';

const SIMPLETEST_SCRIPT_COLOR_PASS = 32; // Green.
const SIMPLETEST_SCRIPT_COLOR_FAIL = 31; // Red.
const SIMPLETEST_SCRIPT_COLOR_EXCEPTION = 33; // Brown.

// Restricting the chunk of queries prevents memory exhaustion.
const SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT = 350;

// Set defaults and get overrides.
list($args, $count) = simpletest_script_parse_args();

if ($args['help'] || $count == 0) {
  simpletest_script_help();
  exit;
}

simpletest_script_init();

$request = Request::createFromGlobals();
$kernel = TestRunnerKernel::createFromRequest($request, $autoloader);
$kernel->prepareLegacyRequest($request);

if ($args['execute-test']) {
  simpletest_script_setup_database();
  simpletest_script_run_one_test($args['test-id'], $args['execute-test']);
  // Sub-process exited already; this is just for clarity.
  exit;
}

if ($args['list']) {
  // Display all available tests.
  echo "\nAvailable test groups & classes\n";
  echo   "-------------------------------\n\n";
  $groups = simpletest_test_get_all($args['module']);
  foreach ($groups as $group => $tests) {
    echo $group . "\n";
    foreach ($tests as $class => $info) {
      echo " - $class\n";
    }
  }
  exit;
}

simpletest_script_setup_database(TRUE);

if ($args['clean']) {
  // Clean up left-over tables and directories.
  simpletest_clean_environment();
  echo "\nEnvironment cleaned.\n";

  // Get the status messages and print them.
  $messages = drupal_get_messages('status');
  foreach ($messages['status'] as $text) {
    echo " - " . $text . "\n";
  }
  exit;
}

$test_list = simpletest_script_get_test_list();

// Try to allocate unlimited time to run the tests.
drupal_set_time_limit(0);
simpletest_script_reporter_init();

$tests_to_run = array();
for ($i = 0; $i < $args['repeat']; $i++) {
  $tests_to_run = array_merge($tests_to_run, $test_list);
}

// Execute tests.
simpletest_script_execute_batch($tests_to_run);

// Stop the timer.
simpletest_script_reporter_timer_stop();

// Display results before database is cleared.
if ($args['browser']) {
  simpletest_script_open_browser();
}
else {
  simpletest_script_reporter_display_results();
}

if ($args['xml']) {
  simpletest_script_reporter_write_xml_results();
}

// Clean up all test results.
if (!$args['keep-results']) {
  simpletest_clean_results_table();
}

// Test complete, exit.
exit;

/**
 * Print help text.
 */
function simpletest_script_help() {
  global $args;

  echo <<<EOF

Run Drupal tests from the shell.

Usage:        {$args['script']} [OPTIONS] <tests>
Example:      {$args['script']} Profile

All arguments are long options.

  --help      Print this page.

  --list      Display all available test groups.

  --clean     Cleans up database tables or directories from previous, failed,
              tests and then exits (no tests are run).

  --url       The base URL of the root directory of this Drupal checkout; e.g.:
                http://drupal.test/
              Required unless the Drupal root directory maps exactly to:
                http://localhost:80/
              Use a https:// URL to force all tests to be run under SSL.

  --sqlite    A pathname to use for the SQLite database of the test runner.
              Required unless this script is executed with a working Drupal
              installation that has Simpletest module installed.
              A relative pathname is interpreted relative to the Drupal root
              directory.
              Note that ':memory:' cannot be used, because this script spawns
              sub-processes. However, you may use e.g. '/tmpfs/test.sqlite'

  --dburl     A URI denoting the database driver, credentials, server hostname,
              and database name to use in tests.
              Required when running tests without a Drupal installation that
              contains default database connection info in settings.php.
              Examples:
                mysql://username:password@localhost/databasename#table_prefix
                sqlite://localhost/relative/path/db.sqlite
                sqlite://localhost//absolute/path/db.sqlite

  --php       The absolute path to the PHP executable. Usually not needed.

  --concurrency [num]

              Run tests in parallel, up to [num] tests at a time.

  --all       Run all available tests.

  --module    Run all tests belonging to the specified module name.
              (e.g., 'node')

  --class     Run tests identified by specific class names, instead of group names.
              A specific test method can be added, for example,
              'Drupal\book\Tests\BookTest::testBookExport'.

  --file      Run tests identified by specific file names, instead of group names.
              Specify the path and the extension
              (i.e. 'core/modules/user/user.test').

  --directory Run all tests found within the specified file directory.

  --xml       <path>

              If provided, test results will be written as xml files to this path.

  --color     Output text format results with color highlighting.

  --verbose   Output detailed assertion messages in addition to summary.

  --keep-results

              Keeps detailed assertion results (in the database) after tests
              have completed. By default, assertion results are cleared.

  --repeat    Number of times to repeat the test.

  --die-on-fail

              Exit test execution immediately upon any failed assertion. This
              allows to access the test site by changing settings.php to use the
              test database and configuration directories. Use in combination
              with --repeat for debugging random test failures.

  --browser   Opens the results in the browser. This enforces --keep-results and
              if you want to also view any pages rendered in the simpletest
              browser you need to add --verbose to the command line.

  --non-html  Removes escaping from output. Useful for reading results on the
              CLI.

  <test1>[,<test2>[,<test3> ...]]

              One or more tests to be run. By default, these are interpreted
              as the names of test groups as shown at
              admin/config/development/testing.
              These group names typically correspond to module names like "User"
              or "Profile" or "System", but there is also a group "Database".
              If --class is specified then these are interpreted as the names of
              specific test classes whose test methods will be run. Tests must
              be separated by commas. Ignored if --all is specified.

To run this script you will normally invoke it from the root directory of your
Drupal installation as the webserver user (differs per configuration), or root:

sudo -u [wwwrun|www-data|etc] php ./core/scripts/{$args['script']}
  --url http://example.com/ --all
sudo -u [wwwrun|www-data|etc] php ./core/scripts/{$args['script']}
  --url http://example.com/ --class "Drupal\block\Tests\BlockTest"

Without a preinstalled Drupal site and enabled Simpletest module, specify a
SQLite database pathname to create and the default database connection info to
use in tests:

sudo -u [wwwrun|www-data|etc] php ./core/scripts/{$args['script']}
  --sqlite /tmpfs/drupal/test.sqlite
  --dburl mysql://username:password@localhost/database
  --url http://example.com/ --all

EOF;
}

/**
 * Parse execution argument and ensure that all are valid.
 *
 * @return The list of arguments.
 */
function simpletest_script_parse_args() {
  // Set default values.
  $args = array(
    'script' => '',
    'help' => FALSE,
    'list' => FALSE,
    'clean' => FALSE,
    'url' => '',
    'sqlite' => NULL,
    'dburl' => NULL,
    'php' => '',
    'concurrency' => 1,
    'all' => FALSE,
    'module' => NULL,
    'class' => FALSE,
    'file' => FALSE,
    'directory' => NULL,
    'color' => FALSE,
    'verbose' => FALSE,
    'keep-results' => FALSE,
    'test_names' => array(),
    'repeat' => 1,
    'die-on-fail' => FALSE,
    'browser' => FALSE,
    // Used internally.
    'test-id' => 0,
    'execute-test' => '',
    'xml' => '',
    'non-html' => FALSE,
  );

  // Override with set values.
  $args['script'] = basename(array_shift($_SERVER['argv']));

  $count = 0;
  while ($arg = array_shift($_SERVER['argv'])) {
    if (preg_match('/--(\S+)/', $arg, $matches)) {
      // Argument found.
      if (array_key_exists($matches[1], $args)) {
        // Argument found in list.
        $previous_arg = $matches[1];
        if (is_bool($args[$previous_arg])) {
          $args[$matches[1]] = TRUE;
        }
        else {
          $args[$matches[1]] = array_shift($_SERVER['argv']);
        }
        // Clear extraneous values.
        $args['test_names'] = array();
        $count++;
      }
      else {
        // Argument not found in list.
        simpletest_script_print_error("Unknown argument '$arg'.");
        exit;
      }
    }
    else {
      // Values found without an argument should be test names.
      $args['test_names'] += explode(',', $arg);
      $count++;
    }
  }

  // Validate the concurrency argument
  if (!is_numeric($args['concurrency']) || $args['concurrency'] <= 0) {
    simpletest_script_print_error("--concurrency must be a strictly positive integer.");
    exit;
  }

  if ($args['browser']) {
    $args['keep-results'] = TRUE;
  }
  return array($args, $count);
}

/**
 * Initialize script variables and perform general setup requirements.
 */
function simpletest_script_init() {
  global $args, $php;

  $host = 'localhost';
  $path = '';
  $port = '80';

  // Determine location of php command automatically, unless a command line argument is supplied.
  if (!empty($args['php'])) {
    $php = $args['php'];
  }
  elseif ($php_env = getenv('_')) {
    // '_' is an environment variable set by the shell. It contains the command that was executed.
    $php = $php_env;
  }
  elseif ($sudo = getenv('SUDO_COMMAND')) {
    // 'SUDO_COMMAND' is an environment variable set by the sudo program.
    // Extract only the PHP interpreter, not the rest of the command.
    list($php, ) = explode(' ', $sudo, 2);
  }
  else {
    simpletest_script_print_error('Unable to automatically determine the path to the PHP interpreter. Supply the --php command line argument.');
    simpletest_script_help();
    exit();
  }

  // Get URL from arguments.
  if (!empty($args['url'])) {
    $parsed_url = parse_url($args['url']);
    $host = $parsed_url['host'] . (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '');
    $path = isset($parsed_url['path']) ? rtrim(rtrim($parsed_url['path']), '/') : '';
    $port = (isset($parsed_url['port']) ? $parsed_url['port'] : $port);
    if ($path == '/') {
      $path = '';
    }
    // If the passed URL schema is 'https' then setup the $_SERVER variables
    // properly so that testing will run under HTTPS.
    if ($parsed_url['scheme'] == 'https') {
      $_SERVER['HTTPS'] = 'on';
    }
  }

  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $base_url = 'https://';
  }
  else {
    $base_url = 'http://';
  }
  $base_url .= $host;
  if ($path !== '') {
    $base_url .= $path;
  }
  putenv('SIMPLETEST_BASE_URL=' . $base_url);
  $_SERVER['HTTP_HOST'] = $host;
  $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
  $_SERVER['SERVER_ADDR'] = '127.0.0.1';
  $_SERVER['SERVER_PORT'] = $port;
  $_SERVER['SERVER_SOFTWARE'] = NULL;
  $_SERVER['SERVER_NAME'] = 'localhost';
  $_SERVER['REQUEST_URI'] = $path .'/';
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['SCRIPT_NAME'] = $path .'/index.php';
  $_SERVER['SCRIPT_FILENAME'] = $path .'/index.php';
  $_SERVER['PHP_SELF'] = $path .'/index.php';
  $_SERVER['HTTP_USER_AGENT'] = 'Drupal command line';

  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    // Ensure that any and all environment variables are changed to https://.
    foreach ($_SERVER as $key => $value) {
      $_SERVER[$key] = str_replace('http://', 'https://', $_SERVER[$key]);
    }
  }

  chdir(realpath(__DIR__ . '/../..'));
}

/**
 * Sets up database connection info for running tests.
 *
 * If this script is executed from within a real Drupal installation, then this
 * function essentially performs nothing (unless the --sqlite or --dburl
 * parameters were passed).
 *
 * Otherwise, there are three database connections of concern:
 * - --sqlite: The test runner connection, providing access to Simpletest
 *   database tables for recording test IDs and assertion results.
 * - --dburl: A database connection that is used as base connection info for all
 *   tests; i.e., every test will spawn from this connection. In case this
 *   connection uses e.g. SQLite, then all tests will run against SQLite. This
 *   is exposed as $databases['default']['default'] to Drupal.
 * - The actual database connection used within a test. This is the same as
 *   --dburl, but uses an additional database table prefix. This is
 *   $databases['default']['default'] within a test environment. The original
 *   connection is retained in
 *   $databases['simpletest_original_default']['default'] and restored after
 *   each test.
 *
 * @param bool $new
 *   Whether this process is a run-tests.sh master process. If TRUE, the SQLite
 *   database file specified by --sqlite (if any) is set up. Otherwise, database
 *   connections are prepared only.
 */
function simpletest_script_setup_database($new = FALSE) {
  global $args;

  // If there is an existing Drupal installation that contains a database
  // connection info in settings.php, then $databases['default']['default'] will
  // hold the default database connection already. This connection is assumed to
  // be valid, and this connection will be used in tests, so that they run
  // against e.g. MySQL instead of SQLite.

  // However, in case no Drupal installation exists, this default database
  // connection can be set and/or overridden with the --dburl parameter.
  if (!empty($args['dburl'])) {
    // Remove a possibly existing default connection (from settings.php).
    Database::removeConnection('default');
    try {
      $databases['default']['default'] = Database::convertDbUrlToConnectionInfo($args['dburl'], DRUPAL_ROOT);
    }
    catch (\InvalidArgumentException $e) {
      simpletest_script_print_error('Invalid --dburl. Reason: ' . $e->getMessage());
      exit(1);
    }
  }
  // Otherwise, use the default database connection from settings.php.
  else {
    $databases['default'] = Database::getConnectionInfo('default');
  }

  // If there is no default database connection for tests, we cannot continue.
  if (!isset($databases['default']['default'])) {
    simpletest_script_print_error('Missing default database connection for tests. Use --dburl to specify one.');
    exit(1);
  }
  Database::addConnectionInfo('default', 'default', $databases['default']['default']);

  // If no --sqlite parameter has been passed, then Simpletest module is assumed
  // to be installed, so the test runner database connection is the default
  // database connection.
  if (empty($args['sqlite'])) {
    $sqlite = FALSE;
    $databases['test-runner']['default'] = $databases['default']['default'];
  }
  // Otherwise, set up a SQLite connection for the test runner.
  else {
    if ($args['sqlite'][0] === '/') {
      $sqlite = $args['sqlite'];
    }
    else {
      $sqlite = DRUPAL_ROOT . '/' . $args['sqlite'];
    }
    $databases['test-runner']['default'] = array(
      'driver' => 'sqlite',
      'database' => $sqlite,
      'prefix' => array(
        'default' => '',
      ),
    );
    // Create the test runner SQLite database, unless it exists already.
    if ($new && !file_exists($sqlite)) {
      if (!is_dir(dirname($sqlite))) {
        mkdir(dirname($sqlite));
      }
      touch($sqlite);
    }
  }

  // Add the test runner database connection.
  Database::addConnectionInfo('test-runner', 'default', $databases['test-runner']['default']);

  // Create the Simpletest schema.
  try {
    $schema = Database::getConnection('default', 'test-runner')->schema();
  }
  catch (\PDOException $e) {
    simpletest_script_print_error($databases['test-runner']['default']['driver'] . ': ' . $e->getMessage());
    exit(1);
  }
  if ($new && $sqlite) {
    require_once DRUPAL_ROOT . '/' . drupal_get_path('module', 'simpletest') . '/simpletest.install';
    foreach (simpletest_schema() as $name => $table_spec) {
      if ($schema->tableExists($name)) {
        $schema->dropTable($name);
      }
      $schema->createTable($name, $table_spec);
    }
  }
  // Verify that the Simpletest database schema exists by checking one table.
  if (!$schema->tableExists('simpletest')) {
    simpletest_script_print_error('Missing Simpletest database schema. Either install Simpletest module or use the --sqlite parameter.');
    exit(1);
  }
}

/**
 * Execute a batch of tests.
 */
function simpletest_script_execute_batch($test_classes) {
  global $args, $test_ids;

  // Multi-process execution.
  $children = array();
  while (!empty($test_classes) || !empty($children)) {
    while (count($children) < $args['concurrency']) {
      if (empty($test_classes)) {
        break;
      }

      $test_id = Database::getConnection('default', 'test-runner')
        ->insert('simpletest_test_id')->useDefaults(array('test_id'))->execute();
      $test_ids[] = $test_id;

      $test_class = array_shift($test_classes);
      // Fork a child process.
      $command = simpletest_script_command($test_id, $test_class);
      $process = proc_open($command, array(), $pipes, NULL, NULL, array('bypass_shell' => TRUE));

      if (!is_resource($process)) {
        echo "Unable to fork test process. Aborting.\n";
        exit;
      }

      // Register our new child.
      $children[] = array(
        'process' => $process,
        'test_id' => $test_id,
        'class' => $test_class,
        'pipes' => $pipes,
      );
    }

    // Wait for children every 200ms.
    usleep(200000);

    // Check if some children finished.
    foreach ($children as $cid => $child) {
      $status = proc_get_status($child['process']);
      if (empty($status['running'])) {
        // The child exited, unregister it.
        proc_close($child['process']);
        if ($status['exitcode']) {
          $message = 'FATAL ' . $child['class'] . ': test runner returned a non-zero error code (' . $status['exitcode'] . ').';
          echo $message . "\n";
          // Insert a fail for xml results.
          TestBase::insertAssert($child['test_id'], $child['class'], FALSE, $message, 'run-tests.sh check');
          /// Ensure that an error line is displayed for the class.
          simpletest_script_reporter_display_summary($child['class'], ['#pass' => 0, '#fail' => 1, '#exception' => 0, '#debug' => 0]);
          if ($args['die-on-fail']) {
            list($db_prefix, ) = simpletest_last_test_get($child['test_id']);
            $test_directory = 'sites/simpletest/' . substr($db_prefix, 10);
            echo 'Simpletest database and files kept and test exited immediately on fail so should be reproducible if you change settings.php to use the database prefix '. $db_prefix . ' and config directories in '. $test_directory . "\n";
            $args['keep-results'] = TRUE;
            // Exit repeat loop immediately.
            $args['repeat'] = -1;
          }
        }
        // Free-up space by removing any potentially created resources.
        if (!$args['keep-results']) {
          simpletest_script_cleanup($child['test_id'], $child['class'], $status['exitcode']);
        }

        // Remove this child.
        unset($children[$cid]);
      }
    }
  }
}

/**
 * Run a group of phpunit tests.
 */
function simpletest_script_run_phpunit($test_id, $class) {

  $reflection = new \ReflectionClass($class);
  if ($reflection->hasProperty('runLimit')) {
    set_time_limit($reflection->getStaticPropertyValue('runLimit'));
  }

  $results = simpletest_run_phpunit_tests($test_id, array($class));
  simpletest_process_phpunit_results($results);

  // Map phpunit results to a data structure we can pass to
  // _simpletest_format_summary_line.
  $summaries = array();
  foreach ($results as $result) {
    if (!isset($summaries[$result['test_class']])) {
      $summaries[$result['test_class']] = array(
        '#pass' => 0,
        '#fail' => 0,
        '#exception' => 0,
        '#debug' => 0,
      );
    }

    switch ($result['status']) {
      case 'pass':
        $summaries[$result['test_class']]['#pass']++;
        break;
      case 'fail':
        $summaries[$result['test_class']]['#fail']++;
        break;
      case 'exception':
        $summaries[$result['test_class']]['#exception']++;
        break;
      case 'debug':
        $summaries[$result['test_class']]['#debug']++;
        break;
    }
  }

  foreach ($summaries as $class => $summary) {
    simpletest_script_reporter_display_summary($class, $summary);
  }
}

/**
 * Bootstrap Drupal and run a single test.
 */
function simpletest_script_run_one_test($test_id, $test_class) {
  global $args;

  try {
    if (strpos($test_class, '::') > 0) {
      list($class_name, $method) = explode('::', $test_class, 2);
      $methods = [$method];
    }
    else {
      $class_name = $test_class;
      // Use empty array to run all the test methods.
      $methods = array();
    }
    $test = new $class_name($test_id);
    if (is_subclass_of($test_class, '\PHPUnit_Framework_TestCase')) {
      simpletest_script_run_phpunit($test_id, $test_class);
    }
    else {
      $test->dieOnFail = (bool) $args['die-on-fail'];
      $test->verbose = (bool) $args['verbose'];
      $test->run($methods);
      simpletest_script_reporter_display_summary($test_class, $test->results);
    }

    // Finished, kill this runner.
    exit(0);
  }
  // DrupalTestCase::run() catches exceptions already, so this is only reached
  // when an exception is thrown in the wrapping test runner environment.
  catch (Exception $e) {
    echo (string) $e;
    exit(1);
  }
}

/**
 * Return a command used to run a test in a separate process.
 *
 * @param $test_id
 *  The current test ID.
 * @param $test_class
 *  The name of the test class to run.
 */
function simpletest_script_command($test_id, $test_class) {
  global $args, $php;

  $command = escapeshellarg($php) . ' ' . escapeshellarg('./core/scripts/' . $args['script']);
  $command .= ' --url ' . escapeshellarg($args['url']);
  if (!empty($args['sqlite'])) {
    $command .= ' --sqlite ' . escapeshellarg($args['sqlite']);
  }
  if (!empty($args['dburl'])) {
    $command .= ' --dburl ' . escapeshellarg($args['dburl']);
  }
  $command .= ' --php ' . escapeshellarg($php);
  $command .= " --test-id $test_id";
  foreach (array('verbose', 'keep-results', 'color', 'die-on-fail') as $arg) {
    if ($args[$arg]) {
      $command .= ' --' . $arg;
    }
  }
  // --execute-test and class name needs to come last.
  $command .= ' --execute-test ' . escapeshellarg($test_class);
  return $command;
}

/**
 * Removes all remnants of a test runner.
 *
 * In case a (e.g., fatal) error occurs after the test site has been fully setup
 * and the error happens in many tests, the environment that executes the tests
 * can easily run out of memory or disk space. This function ensures that all
 * created resources are properly cleaned up after every executed test.
 *
 * This clean-up only exists in this script, since SimpleTest module itself does
 * not use isolated sub-processes for each test being run, so a fatal error
 * halts not only the test, but also the test runner (i.e., the parent site).
 *
 * @param int $test_id
 *   The test ID of the test run.
 * @param string $test_class
 *   The class name of the test run.
 * @param int $exitcode
 *   The exit code of the test runner.
 *
 * @see simpletest_script_run_one_test()
 */
function simpletest_script_cleanup($test_id, $test_class, $exitcode) {
  if (is_subclass_of($test_class, '\PHPUnit_Framework_TestCase')) {
    // PHPUnit test, move on.
    return;
  }
  // Retrieve the last database prefix used for testing.
  list($db_prefix, ) = simpletest_last_test_get($test_id);

  // If no database prefix was found, then the test was not set up correctly.
  if (empty($db_prefix)) {
    echo "\nFATAL $test_class: Found no database prefix for test ID $test_id. (Check whether setUp() is invoked correctly.)";
    return;
  }

  // Do not output verbose cleanup messages in case of a positive exitcode.
  $output = !empty($exitcode);
  $messages = array();

  $messages[] = "- Found database prefix '$db_prefix' for test ID $test_id.";

  // Read the log file in case any fatal errors caused the test to crash.
  simpletest_log_read($test_id, $db_prefix, $test_class);

  // Check whether a test site directory was setup already.
  // @see \Drupal\simpletest\TestBase::prepareEnvironment()
  $test_directory = DRUPAL_ROOT . '/sites/simpletest/' . substr($db_prefix, 10);
  if (is_dir($test_directory)) {
    // Output the error_log.
    if (is_file($test_directory . '/error.log')) {
      if ($errors = file_get_contents($test_directory . '/error.log')) {
        $output = TRUE;
        $messages[] = $errors;
      }
    }
    // Delete the test site directory.
    // simpletest_clean_temporary_directories() cannot be used here, since it
    // would also delete file directories of other tests that are potentially
    // running concurrently.
    file_unmanaged_delete_recursive($test_directory, array('Drupal\simpletest\TestBase', 'filePreDeleteCallback'));
    $messages[] = "- Removed test site directory.";
  }

  // Clear out all database tables from the test.
  $schema = Database::getConnection('default', 'default')->schema();
  $count = 0;
  foreach ($schema->findTables($db_prefix . '%') as $table) {
    $schema->dropTable($table);
    $count++;
  }
  if ($count) {
    $messages[] = "- Removed $count leftover tables.";
  }

  if ($output) {
    echo implode("\n", $messages);
    echo "\n";
  }
}

/**
 * Get list of tests based on arguments. If --all specified then
 * returns all available tests, otherwise reads list of tests.
 *
 * Will print error and exit if no valid tests were found.
 *
 * @return List of tests.
 */
function simpletest_script_get_test_list() {
  global $args;

  $test_list = array();
  if ($args['all'] || $args['module']) {
    $groups = simpletest_test_get_all($args['module']);
    $all_tests = array();
    foreach ($groups as $group => $tests) {
      $all_tests = array_merge($all_tests, array_keys($tests));
    }
    $test_list = $all_tests;
  }
  else {
    if ($args['class']) {
      $test_list = array();
      foreach ($args['test_names'] as $test_class) {
        list($class_name, ) = explode('::', $test_class, 2);
        if (class_exists($class_name)) {
          $test_list[] = $test_class;
        }
        else {
          $groups = simpletest_test_get_all();
          $all_classes = array();
          foreach ($groups as $group) {
            $all_classes = array_merge($all_classes, array_keys($group));
          }
          simpletest_script_print_error('Test class not found: ' . $class_name);
          simpletest_script_print_alternatives($class_name, $all_classes, 6);
          exit(1);
        }
      }
    }
    elseif ($args['file']) {
      // Extract test case class names from specified files.
      foreach ($args['test_names'] as $file) {
        if (!file_exists($file)) {
          simpletest_script_print_error('File not found: ' . $file);
          exit;
        }
        $content = file_get_contents($file);
        // Extract a potential namespace.
        $namespace = FALSE;
        if (preg_match('@^namespace ([^ ;]+)@m', $content, $matches)) {
          $namespace = $matches[1];
        }
        // Extract all class names.
        // Abstract classes are excluded on purpose.
        preg_match_all('@^class ([^ ]+)@m', $content, $matches);
        if (!$namespace) {
          $test_list = array_merge($test_list, $matches[1]);
        }
        else {
          foreach ($matches[1] as $class_name) {
            $namespace_class = $namespace . '\\' . $class_name;
            if (is_subclass_of($namespace_class, '\Drupal\simpletest\TestBase') || is_subclass_of($namespace_class, '\PHPUnit_Framework_TestCase')) {
              $test_list[] = $namespace_class;
            }
          }
        }
      }
    }
    elseif ($args['directory']) {
      // Extract test case class names from specified directory.
      // Find all tests in the PSR-X structure; Drupal\$extension\Tests\*.php
      // Since we do not want to hard-code too many structural file/directory
      // assumptions about PSR-0/4 files and directories, we check for the
      // minimal conditions only; i.e., a '*.php' file that has '/Tests/' in
      // its path.
      // Ignore anything from third party vendors.
      $ignore = array('.', '..', 'vendor');
      $files = [];
      if ($args['directory'][0] === '/') {
        $directory = $args['directory'];
      }
      else {
        $directory = DRUPAL_ROOT . "/" . $args['directory'];
      }
      foreach (file_scan_directory($directory, '/\.php$/', $ignore) as $file) {
        // '/Tests/' can be contained anywhere in the file's path (there can be
        // sub-directories below /Tests), but must be contained literally.
        // Case-insensitive to match all Simpletest and PHPUnit tests:
        //   ./lib/Drupal/foo/Tests/Bar/Baz.php
        //   ./foo/src/Tests/Bar/Baz.php
        //   ./foo/tests/Drupal/foo/Tests/FooTest.php
        //   ./foo/tests/src/FooTest.php
        // $file->filename doesn't give us a directory, so we use $file->uri
        // Strip the drupal root directory and trailing slash off the URI
        $filename = substr($file->uri, strlen(DRUPAL_ROOT)+1);
        if (stripos($filename, '/Tests/')) {
          $files[$filename] = $filename;
        }
      }
      foreach ($files as $file) {
        $content = file_get_contents($file);
        // Extract a potential namespace.
        $namespace = FALSE;
        if (preg_match('@^namespace ([^ ;]+)@m', $content, $matches)) {
          $namespace = $matches[1];
        }
        // Extract all class names.
        // Abstract classes are excluded on purpose.
        preg_match_all('@^class ([^ ]+)@m', $content, $matches);
        if (!$namespace) {
          $test_list = array_merge($test_list, $matches[1]);
        }
        else {
          foreach ($matches[1] as $class_name) {
            $namespace_class = $namespace . '\\' . $class_name;
            if (is_subclass_of($namespace_class, '\Drupal\simpletest\TestBase') || is_subclass_of($namespace_class, '\PHPUnit_Framework_TestCase')) {
              $test_list[] = $namespace_class;
            }
          }
        }
      }
    }
    else {
      $groups = simpletest_test_get_all();
      foreach ($args['test_names'] as $group_name) {
        if (isset($groups[$group_name])) {
          $test_list = array_merge($test_list, array_keys($groups[$group_name]));
        }
        else {
          simpletest_script_print_error('Test group not found: ' . $group_name);
          simpletest_script_print_alternatives($group_name, array_keys($groups));
          exit(1);
        }
      }
    }
  }

  if (empty($test_list)) {
    simpletest_script_print_error('No valid tests were specified.');
    exit;
  }
  return $test_list;
}

/**
 * Initialize the reporter.
 */
function simpletest_script_reporter_init() {
  global $args, $test_list, $results_map;

  $results_map = array(
    'pass' => 'Pass',
    'fail' => 'Fail',
    'exception' => 'Exception'
  );

  echo "\n";
  echo "Drupal test run\n";
  echo "---------------\n";
  echo "\n";

  // Tell the user about what tests are to be run.
  if ($args['all']) {
    echo "All tests will run.\n\n";
  }
  else {
    echo "Tests to be run:\n";
    foreach ($test_list as $class_name) {
      echo "  - $class_name\n";
    }
    echo "\n";
  }

  echo "Test run started:\n";
  echo "  " . date('l, F j, Y - H:i', $_SERVER['REQUEST_TIME']) . "\n";
  Timer::start('run-tests');
  echo "\n";

  echo "Test summary\n";
  echo "------------\n";
  echo "\n";
}

/**
 * Displays the assertion result summary for a single test class.
 *
 * @param string $class
 *   The test class name that was run.
 * @param array $results
 *   The assertion results using #pass, #fail, #exception, #debug array keys.
 */
function simpletest_script_reporter_display_summary($class, $results) {
  // Output all test results vertically aligned.
  // Cut off the class name after 60 chars, and pad each group with 3 digits
  // by default (more than 999 assertions are rare).
  $output = vsprintf('%-60.60s %10s %9s %14s %12s', array(
    $class,
                                   $results['#pass']      . ' passes',
    !$results['#fail']      ? '' : $results['#fail']      . ' fails',
    !$results['#exception'] ? '' : $results['#exception'] . ' exceptions',
    !$results['#debug']     ? '' : $results['#debug']     . ' messages',
  ));

  $status = ($results['#fail'] || $results['#exception'] ? 'fail' : 'pass');
  simpletest_script_print($output . "\n", simpletest_script_color_code($status));
}

/**
 * Display jUnit XML test results.
 */
function simpletest_script_reporter_write_xml_results() {
  global $args, $test_ids, $results_map;

  $results = simpletest_script_load_messages_by_test_id($test_ids);

  $test_class = '';
  $xml_files = array();

  foreach ($results as $result) {
    if (isset($results_map[$result->status])) {
      if ($result->test_class != $test_class) {
        // We've moved onto a new class, so write the last classes results to a file:
        if (isset($xml_files[$test_class])) {
          file_put_contents($args['xml'] . '/' . str_replace('\\', '_', $test_class) . '.xml', $xml_files[$test_class]['doc']->saveXML());
          unset($xml_files[$test_class]);
        }
        $test_class = $result->test_class;
        if (!isset($xml_files[$test_class])) {
          $doc = new DomDocument('1.0');
          $root = $doc->createElement('testsuite');
          $root = $doc->appendChild($root);
          $xml_files[$test_class] = array('doc' => $doc, 'suite' => $root);
        }
      }

      // For convenience:
      $dom_document = &$xml_files[$test_class]['doc'];

      // Create the XML element for this test case:
      $case = $dom_document->createElement('testcase');
      $case->setAttribute('classname', $test_class);
      if (strpos($result->function, '->') !== FALSE) {
        list($class, $name) = explode('->', $result->function, 2);
      }
      else {
        $name = $result->function;
      }
      $case->setAttribute('name', $name);

      // Passes get no further attention, but failures and exceptions get to add more detail:
      if ($result->status == 'fail') {
        $fail = $dom_document->createElement('failure');
        $fail->setAttribute('type', 'failure');
        $fail->setAttribute('message', $result->message_group);
        $text = $dom_document->createTextNode($result->message);
        $fail->appendChild($text);
        $case->appendChild($fail);
      }
      elseif ($result->status == 'exception') {
        // In the case of an exception the $result->function may not be a class
        // method so we record the full function name:
        $case->setAttribute('name', $result->function);

        $fail = $dom_document->createElement('error');
        $fail->setAttribute('type', 'exception');
        $fail->setAttribute('message', $result->message_group);
        $full_message = $result->message . "\n\nline: " . $result->line . "\nfile: " . $result->file;
        $text = $dom_document->createTextNode($full_message);
        $fail->appendChild($text);
        $case->appendChild($fail);
      }
      // Append the test case XML to the test suite:
      $xml_files[$test_class]['suite']->appendChild($case);
    }
  }
  // The last test case hasn't been saved to a file yet, so do that now:
  if (isset($xml_files[$test_class])) {
    file_put_contents($args['xml'] . '/' . str_replace('\\', '_', $test_class) . '.xml', $xml_files[$test_class]['doc']->saveXML());
    unset($xml_files[$test_class]);
  }
}

/**
 * Stop the test timer.
 */
function simpletest_script_reporter_timer_stop() {
  echo "\n";
  $end = Timer::stop('run-tests');
  echo "Test run duration: " . \Drupal::service('date.formatter')->formatInterval($end['time'] / 1000);
  echo "\n\n";
}

/**
 * Display test results.
 */
function simpletest_script_reporter_display_results() {
  global $args, $test_ids, $results_map;

  if ($args['verbose']) {
    // Report results.
    echo "Detailed test results\n";
    echo "---------------------\n";

    $results = simpletest_script_load_messages_by_test_id($test_ids);
    $test_class = '';
    foreach ($results as $result) {
      if (isset($results_map[$result->status])) {
        if ($result->test_class != $test_class) {
          // Display test class every time results are for new test class.
          echo "\n\n---- $result->test_class ----\n\n\n";
          $test_class = $result->test_class;

          // Print table header.
          echo "Status    Group      Filename          Line Function                            \n";
          echo "--------------------------------------------------------------------------------\n";
        }

        simpletest_script_format_result($result);
      }
    }
  }
}

/**
 * Format the result so that it fits within the default 80 character
 * terminal size.
 *
 * @param $result The result object to format.
 */
function simpletest_script_format_result($result) {
  global $args, $results_map, $color;

  $summary = sprintf("%-9.9s %-10.10s %-17.17s %4.4s %-35.35s\n",
    $results_map[$result->status], $result->message_group, basename($result->file), $result->line, $result->function);

  simpletest_script_print($summary, simpletest_script_color_code($result->status));

  $message = trim(strip_tags($result->message));
  if ($args['non-html']) {
    $message = Html::decodeEntities($message, ENT_QUOTES, 'UTF-8');
  }
  $lines = explode("\n", wordwrap($message), 76);
  foreach ($lines as $line) {
    echo "    $line\n";
  }
}

/**
 * Print error message prefixed with "  ERROR: " and displayed in fail color
 * if color output is enabled.
 *
 * @param $message The message to print.
 */
function simpletest_script_print_error($message) {
  simpletest_script_print("  ERROR: $message\n", SIMPLETEST_SCRIPT_COLOR_FAIL);
}

/**
 * Print a message to the console, if color is enabled then the specified
 * color code will be used.
 *
 * @param $message The message to print.
 * @param $color_code The color code to use for coloring.
 */
function simpletest_script_print($message, $color_code) {
  global $args;
  if ($args['color']) {
    echo "\033[" . $color_code . "m" . $message . "\033[0m";
  }
  else {
    echo $message;
  }
}

/**
 * Get the color code associated with the specified status.
 *
 * @param $status The status string to get code for.
 * @return Color code.
 */
function simpletest_script_color_code($status) {
  switch ($status) {
    case 'pass':
      return SIMPLETEST_SCRIPT_COLOR_PASS;
    case 'fail':
      return SIMPLETEST_SCRIPT_COLOR_FAIL;
    case 'exception':
      return SIMPLETEST_SCRIPT_COLOR_EXCEPTION;
  }
  return 0; // Default formatting.
}

/**
 * Prints alternative test names.
 *
 * Searches the provided array of string values for close matches based on the
 * Levenshtein algorithm.
 *
 * @see http://php.net/manual/en/function.levenshtein.php
 *
 * @param string $string
 *   A string to test.
 * @param array $array
 *   A list of strings to search.
 * @param int $degree
 *   The matching strictness. Higher values return fewer matches. A value of
 *   4 means that the function will return strings from $array if the candidate
 *   string in $array would be identical to $string by changing 1/4 or fewer of
 *   its characters.
 */
function simpletest_script_print_alternatives($string, $array, $degree = 4) {
  $alternatives = array();
  foreach ($array as $item) {
    $lev = levenshtein($string, $item);
    if ($lev <= strlen($item) / $degree || FALSE !== strpos($string, $item)) {
      $alternatives[] = $item;
    }
  }
  if (!empty($alternatives)) {
    simpletest_script_print("  Did you mean?\n", SIMPLETEST_SCRIPT_COLOR_FAIL);
    foreach ($alternatives as $alternative) {
      simpletest_script_print("  - $alternative\n", SIMPLETEST_SCRIPT_COLOR_FAIL);
    }
  }
}

/**
 * Loads the simpletest messages from the database.
 *
 * Messages are ordered by test class and message id.
 *
 * @param array $test_ids
 *   Array of test IDs of the messages to be loaded.
 *
 * @return array
 *   Array of simpletest messages from the database.
 */
function simpletest_script_load_messages_by_test_id($test_ids) {
  global $args;
  $results = array();

  // Sqlite has a maximum number of variables per query. If required, the
  // database query is split into chunks.
  if (count($test_ids) > SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT && !empty($args['sqlite'])) {
    $test_id_chunks = array_chunk($test_ids, SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT);
  }
  else {
    $test_id_chunks = array($test_ids);
  }

  foreach ($test_id_chunks as $test_id_chunk) {
    $result_chunk = Database::getConnection('default', 'test-runner')
      ->query("SELECT * FROM {simpletest} WHERE test_id IN ( :test_ids[] ) ORDER BY test_class, message_id", array(
        ':test_ids[]' => $test_id_chunk,
      ))->fetchAll();
    if ($result_chunk) {
      $results = array_merge($results, $result_chunk);
    }
  }

  return $results;
}

/**
 * Display test results.
 */
function simpletest_script_open_browser() {
  global $test_ids;

  $connection = Database::getConnection('default', 'test-runner');
  $results = $connection->select('simpletest')
    ->fields('simpletest')
    ->condition('test_id', $test_ids, 'IN')
    ->orderBy('test_class')
    ->orderBy('message_id')
    ->execute()
    ->fetchAll();

  // Get the results form.
  $form = array();
  SimpletestResultsForm::addResultForm($form, $results);

  // Get the assets to make the details element collapsible and theme the result
  // form.
  $assets = new \Drupal\Core\Asset\AttachedAssets();
  $assets->setLibraries(['core/drupal.collapse', 'system/admin', 'simpletest/drupal.simpletest']);
  $resolver = \Drupal::service('asset.resolver');
  list($js_assets_header, $js_assets_footer) = $resolver->getJsAssets($assets, FALSE);
  $js_collection_renderer = \Drupal::service('asset.js.collection_renderer');
  $js_assets_header = $js_collection_renderer->render($js_assets_header);
  $js_assets_footer = $js_collection_renderer->render($js_assets_footer);
  $css_assets = \Drupal::service('asset.css.collection_renderer')->render($resolver->getCssAssets($assets, FALSE));

  // Make the html page to write to disk.
  $render_service = \Drupal::service('renderer');
  $html = '<head>' . $render_service->renderPlain($js_assets_header) . $render_service->renderPlain($css_assets) . '</head><body>' . $render_service->renderPlain($form) . $render_service->renderPlain($js_assets_footer) .'</body>';

  // Ensure we have assets verbose directory - tests with no verbose output will not
  // have created one.
  $directory = PublicStream::basePath() . '/simpletest/verbose';
  file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
  $uuid = new Php();
  $filename = $directory .'/results-'. $uuid->generate() .'.html';
  file_put_contents($filename, $html);

  // See if we can find an OS helper to open URLs in default browser.
  $browser = FALSE;
  if (shell_exec('which xdg-open')) {
    $browser = 'xdg-open';
  }
  elseif (shell_exec('which open')) {
    $browser = 'open';
  }
  elseif (substr(PHP_OS, 0, 3) == 'WIN') {
    $browser = 'start';
  }

  if ($browser) {
    shell_exec($browser . ' ' . escapeshellarg($filename));
  }
  else {
    // Can't find assets valid browser.
    print 'Open file://' . realpath($filename) . ' in your browser to see the verbose output.';
  }
}
