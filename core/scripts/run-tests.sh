<?php

/**
 * @file
 * Script for running tests on DrupalCI.
 *
 * This script is intended for use only by drupal.org's testing. In general,
 * tests should be run directly with phpunit.
 *
 * @internal
 */

use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Composer\Composer;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\EnvironmentCleaner;
use Drupal\Core\Test\PhpUnitTestRunner;
use Drupal\Core\Test\SimpletestTestRunResultsStorage;
use Drupal\Core\Test\RunTests\TestFileParser;
use Drupal\Core\Test\TestDatabase;
use Drupal\Core\Test\TestRun;
use Drupal\Core\Test\TestRunnerKernel;
use Drupal\Core\Test\TestRunResultsStorageInterface;
use Drupal\Core\Test\TestDiscovery;
use Drupal\TestTools\PhpUnitCompatibility\ClassWriter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;

// Define some colors for display.
// A nice calming green.
const SIMPLETEST_SCRIPT_COLOR_PASS = 32;
// An alerting Red.
const SIMPLETEST_SCRIPT_COLOR_FAIL = 31;
// An annoying brown.
const SIMPLETEST_SCRIPT_COLOR_EXCEPTION = 33;

// Restricting the chunk of queries prevents memory exhaustion.
const SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT = 350;

const SIMPLETEST_SCRIPT_EXIT_SUCCESS = 0;
const SIMPLETEST_SCRIPT_EXIT_FAILURE = 1;
const SIMPLETEST_SCRIPT_EXIT_EXCEPTION = 2;

// Set defaults and get overrides.
[$args, $count] = simpletest_script_parse_args();

if ($args['help'] || $count == 0) {
  simpletest_script_help();
  exit(($count == 0) ? SIMPLETEST_SCRIPT_EXIT_FAILURE : SIMPLETEST_SCRIPT_EXIT_SUCCESS);
}

simpletest_script_init();

if (!class_exists(TestCase::class)) {
  echo "\nrun-tests.sh requires the PHPUnit testing framework. Use 'composer install' to ensure that it is present.\n\n";
  exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
}

if ($args['execute-test']) {
  simpletest_script_setup_database();
  $test_run_results_storage = simpletest_script_setup_test_run_results_storage();
  $test_run = TestRun::get($test_run_results_storage, $args['test-id']);
  simpletest_script_run_one_test($test_run, $args['execute-test']);
  // Sub-process exited already; this is just for clarity.
  exit(SIMPLETEST_SCRIPT_EXIT_SUCCESS);
}

if ($args['list']) {
  // Display all available tests organized by one @group annotation.
  echo "\nAvailable test groups & classes\n";
  echo "-------------------------------\n\n";
  $test_discovery = new TestDiscovery(
    \Drupal::root(),
    \Drupal::service('class_loader')
  );
  try {
    $groups = $test_discovery->getTestClasses($args['module']);
  }
  catch (Exception $e) {
    error_log((string) $e);
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }

  // A given class can appear in multiple groups. For historical reasons, we
  // need to present each test only once. The test is shown in the group that is
  // printed first.
  $printed_tests = [];
  foreach ($groups as $group => $tests) {
    echo $group . "\n";
    $tests = array_diff(array_keys($tests), $printed_tests);
    foreach ($tests as $test) {
      echo " - $test\n";
    }
    $printed_tests = array_merge($printed_tests, $tests);
  }
  exit(SIMPLETEST_SCRIPT_EXIT_SUCCESS);
}

// List-files and list-files-json provide a way for external tools such as the
// testbot to prioritize running changed tests.
// @see https://www.drupal.org/node/2569585
if ($args['list-files'] || $args['list-files-json']) {
  // List all files which could be run as tests.
  $test_discovery = new TestDiscovery(
    \Drupal::root(),
    \Drupal::service('class_loader')
  );
  // TestDiscovery::findAllClassFiles() gives us a classmap similar to a
  // Composer 'classmap' array.
  $test_classes = $test_discovery->findAllClassFiles();
  // JSON output is the easiest.
  if ($args['list-files-json']) {
    echo json_encode($test_classes);
    exit(SIMPLETEST_SCRIPT_EXIT_SUCCESS);
  }
  // Output the list of files.
  else {
    foreach (array_values($test_classes) as $test_class) {
      echo $test_class . "\n";
    }
  }
  exit(SIMPLETEST_SCRIPT_EXIT_SUCCESS);
}

simpletest_script_setup_database(TRUE);

// Setup the test run results storage environment. Currently, this coincides
// with the simpletest database schema.
$test_run_results_storage = simpletest_script_setup_test_run_results_storage(TRUE);

if ($args['clean']) {
  // Clean up left-over tables and directories.
  $cleaner = new EnvironmentCleaner(
    DRUPAL_ROOT,
    Database::getConnection(),
    $test_run_results_storage,
    new ConsoleOutput(),
    \Drupal::service('file_system')
  );
  try {
    $cleaner->cleanEnvironment();
  }
  catch (Exception $e) {
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }
  echo "\nEnvironment cleaned.\n";

  // Get the status messages and print them.
  $messages = \Drupal::messenger()->messagesByType('status');
  foreach ($messages as $text) {
    echo " - " . $text . "\n";
  }
  exit(SIMPLETEST_SCRIPT_EXIT_SUCCESS);
}

if (!Composer::upgradePHPUnitCheck(Version::id())) {
  simpletest_script_print_error("PHPUnit testing framework version 9 or greater is required when running on PHP 7.4 or greater. Run the command 'composer run-script drupal-phpunit-upgrade' in order to fix this.");
  exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
}

$test_list = simpletest_script_get_test_list();

// Try to allocate unlimited time to run the tests.
Environment::setTimeLimit(0);
simpletest_script_reporter_init();

$tests_to_run = [];
for ($i = 0; $i < $args['repeat']; $i++) {
  $tests_to_run = array_merge($tests_to_run, $test_list);
}

// Execute tests.
$status = simpletest_script_execute_batch($test_run_results_storage, $tests_to_run);

// Stop the timer.
simpletest_script_reporter_timer_stop();

// Ensure all test locks are released once finished. If tests are run with a
// concurrency of 1 the each test will clean up its own lock. Test locks are
// not released if using a higher concurrency to ensure each test has unique
// fixtures.
TestDatabase::releaseAllTestLocks();

// Display results before database is cleared.
simpletest_script_reporter_display_results($test_run_results_storage);

if ($args['xml']) {
  simpletest_script_reporter_write_xml_results($test_run_results_storage);
}

// Clean up all test results.
if (!$args['keep-results']) {
  try {
    $cleaner = new EnvironmentCleaner(
      DRUPAL_ROOT,
      Database::getConnection(),
      $test_run_results_storage,
      new ConsoleOutput(),
      \Drupal::service('file_system')
    );
    $cleaner->cleanResults();
  }
  catch (Exception $e) {
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }
}

// Test complete, exit.
exit($status);

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

  --list-files
              Display all discoverable test file paths.

  --list-files-json
              Display all discoverable test files as JSON. The array key will be
              the test class name, and the value will be the file path of the
              test.

  --clean     Cleans up database tables or directories from previous, failed,
              tests and then exits (no tests are run).

  --url       The base URL of the root directory of this Drupal checkout; e.g.:
                http://drupal.test/
              Required unless the Drupal root directory maps exactly to:
                http://localhost:80/
              Use a https:// URL to force all tests to be run under SSL.

  --sqlite    A pathname to use for the SQLite database of the test runner.
              Required unless this script is executed with a working Drupal
              installation.
              A relative pathname is interpreted relative to the Drupal root
              directory.
              Note that ':memory:' cannot be used, because this script spawns
              sub-processes. However, you may use e.g. '/tmpfs/test.sqlite'

  --keep-results-table

              Boolean flag to indicate to not cleanup the simpletest result
              table. For testbots or repeated execution of a single test it can
              be helpful to not cleanup the simpletest result table.

  --dburl     A URI denoting the database driver, credentials, server hostname,
              and database name to use in tests.
              Required when running tests without a Drupal installation that
              contains default database connection info in settings.php.
              Examples:
                mysql://username:password@localhost/database_name#table_prefix
                sqlite://localhost/relative/path/db.sqlite
                sqlite://localhost//absolute/path/db.sqlite

  --php       The absolute path to the PHP executable. Usually not needed.

  --concurrency [num]

              Run tests in parallel, up to [num] tests at a time.

  --all       Run all available tests.

  --module    Run all tests belonging to the specified module name.
              (e.g., 'node')

  --class     Run tests identified by specific class names, instead of group names.

  --file      Run tests identified by specific file names, instead of group names.
              Specify the path and the extension
              (i.e. 'core/modules/user/user.test'). This argument must be last
              on the command line.

  --types

              Runs just tests from the specified test type, for example
              run-tests.sh
              (i.e. --types "PHPUnit-Unit,PHPUnit-Kernel")

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

  --non-html  Removes escaping from output. Useful for reading results on the
              CLI.

  --suppress-deprecations

              Stops tests from failing if deprecation errors are triggered. If
              this is not set the value specified in the
              SYMFONY_DEPRECATIONS_HELPER environment variable, or the value
              specified in core/phpunit.xml (if it exists), or the default value
              will be used. The default is that any unexpected silenced
              deprecation error will fail tests.

  --ci-parallel-node-total

              The total number of instances of this job running in parallel.

  --ci-parallel-node-index

              The index of the job in the job set.

  <test1>[,<test2>[,<test3> ...]]

              One or more tests to be run. By default, these are interpreted
              as the names of test groups which are derived from test class
              @group annotations.
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
  --url http://example.com/ --class Drupal\block\Tests\BlockTest

Without a preinstalled Drupal site, specify a SQLite database pathname to create
and the default database connection info to use in tests:

sudo -u [wwwrun|www-data|etc] php ./core/scripts/{$args['script']}
  --sqlite /tmpfs/drupal/test.sqlite
  --dburl mysql://username:password@localhost/database
  --url http://example.com/ --all

EOF;
}

/**
 * Parse execution argument and ensure that all are valid.
 *
 * @return array
 *   The list of arguments.
 */
function simpletest_script_parse_args() {
  // Set default values.
  $args = [
    'script' => '',
    'help' => FALSE,
    'list' => FALSE,
    'list-files' => FALSE,
    'list-files-json' => FALSE,
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
    'types' => [],
    'directory' => NULL,
    'color' => FALSE,
    'verbose' => FALSE,
    'keep-results' => FALSE,
    'keep-results-table' => FALSE,
    'test_names' => [],
    'repeat' => 1,
    'die-on-fail' => FALSE,
    'suppress-deprecations' => FALSE,
    // Used internally.
    'test-id' => 0,
    'execute-test' => '',
    'xml' => '',
    'non-html' => FALSE,
    'ci-parallel-node-index' => 1,
    'ci-parallel-node-total' => 1,
  ];

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
        elseif (is_array($args[$previous_arg])) {
          $value = array_shift($_SERVER['argv']);
          $args[$matches[1]] = array_map('trim', explode(',', $value));
        }
        else {
          $args[$matches[1]] = array_shift($_SERVER['argv']);
        }
        // Clear extraneous values.
        $args['test_names'] = [];
        $count++;
      }
      else {
        // Argument not found in list.
        simpletest_script_print_error("Unknown argument '$arg'.");
        exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
      }
    }
    else {
      // Values found without an argument should be test names.
      $args['test_names'] += explode(',', $arg);
      $count++;
    }
  }

  // Validate the concurrency argument.
  if (!is_numeric($args['concurrency']) || $args['concurrency'] <= 0) {
    simpletest_script_print_error("--concurrency must be a strictly positive integer.");
    exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
  }

  return [$args, $count];
}

/**
 * Initialize script variables and perform general setup requirements.
 */
function simpletest_script_init() {
  global $args, $php;

  $host = 'localhost';
  $path = '';
  $port = '80';

  // Determine location of php command automatically, unless a command line
  // argument is supplied.
  if (!empty($args['php'])) {
    $php = $args['php'];
  }
  elseif ($php_env = getenv('_')) {
    // '_' is an environment variable set by the shell. It contains the command
    // that was executed.
    $php = $php_env;
  }
  elseif ($sudo = getenv('SUDO_COMMAND')) {
    // 'SUDO_COMMAND' is an environment variable set by the sudo program.
    // Extract only the PHP interpreter, not the rest of the command.
    [$php] = explode(' ', $sudo, 2);
  }
  else {
    simpletest_script_print_error('Unable to automatically determine the path to the PHP interpreter. Supply the --php command line argument.');
    simpletest_script_help();
    exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
  }

  // Detect if we're in the top-level process using the private 'execute-test'
  // argument. Determine if being run on drupal.org's testing infrastructure
  // using the presence of 'drupalci' in the sqlite argument.
  // @todo https://www.drupal.org/project/drupalci_testbot/issues/2860941 Use
  //   better environment variable to detect DrupalCI.
  if (!$args['execute-test'] && preg_match('/drupalci/', $args['sqlite'] ?? '')) {
    // Update PHPUnit if needed and possible. There is a later check once the
    // autoloader is in place to ensure we're on the correct version. We need to
    // do this before the autoloader is in place to ensure that it is correct.
    $composer = ($composer = rtrim('\\' === DIRECTORY_SEPARATOR ? preg_replace('/[\r\n].*/', '', `where.exe composer.phar`) : `which composer.phar`))
      ? $php . ' ' . escapeshellarg($composer)
      : 'composer';
    passthru("$composer run-script drupal-phpunit-upgrade-check");
  }

  $autoloader = require_once __DIR__ . '/../../autoload.php';
  // The PHPUnit compatibility layer needs to be available to autoload tests.
  $autoloader->add('Drupal\\TestTools', __DIR__ . '/../tests');
  ClassWriter::mutateTestBase($autoloader);

  // Get URL from arguments.
  if (!empty($args['url'])) {
    $parsed_url = parse_url($args['url']);
    $host = $parsed_url['host'] . (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '');
    $path = isset($parsed_url['path']) ? rtrim(rtrim($parsed_url['path']), '/') : '';
    $port = $parsed_url['port'] ?? $port;
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
  $_SERVER['REQUEST_URI'] = $path . '/';
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['SCRIPT_NAME'] = $path . '/index.php';
  $_SERVER['SCRIPT_FILENAME'] = $path . '/index.php';
  $_SERVER['PHP_SELF'] = $path . '/index.php';
  $_SERVER['HTTP_USER_AGENT'] = 'Drupal command line';

  if ($args['concurrency'] > 1) {
    $directory = FileSystem::getOsTemporaryDirectory();
    $test_symlink = @symlink(__FILE__, $directory . '/test_symlink');
    if (!$test_symlink) {
      throw new \RuntimeException('In order to use a concurrency higher than 1 the test system needs to be able to create symlinks in ' . $directory);
    }
    unlink($directory . '/test_symlink');
    putenv('RUN_TESTS_CONCURRENCY=' . $args['concurrency']);
  }

  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    // Ensure that any and all environment variables are changed to https://.
    foreach ($_SERVER as $key => $value) {
      // Some values are NULL. Non-NULL values which are falsy will not contain
      // text to replace.
      if ($value) {
        $_SERVER[$key] = str_replace('http://', 'https://', $value);
      }
    }
  }

  chdir(realpath(__DIR__ . '/../..'));

  // Prepare the kernel.
  try {
    $request = Request::createFromGlobals();
    $kernel = TestRunnerKernel::createFromRequest($request, $autoloader);
    $kernel->boot();
    $kernel->preHandle($request);
  }
  catch (Exception $e) {
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }
}

/**
 * Sets up database connection info for running tests.
 *
 * If this script is executed from within a real Drupal installation, then this
 * function essentially performs nothing (unless the --sqlite or --dburl
 * parameters were passed).
 *
 * Otherwise, there are three database connections of concern:
 * - --sqlite: The test runner connection, providing access to database tables
 *   for recording test IDs and assertion results.
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
      $databases['default']['default'] = Database::convertDbUrlToConnectionInfo($args['dburl'], DRUPAL_ROOT, TRUE);
    }
    catch (\InvalidArgumentException $e) {
      simpletest_script_print_error('Invalid --dburl. Reason: ' . $e->getMessage());
      exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
    }
  }
  // Otherwise, use the default database connection from settings.php.
  else {
    $databases['default'] = Database::getConnectionInfo('default');
  }

  // If there is no default database connection for tests, we cannot continue.
  if (!isset($databases['default']['default'])) {
    simpletest_script_print_error('Missing default database connection for tests. Use --dburl to specify one.');
    exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
  }
  Database::addConnectionInfo('default', 'default', $databases['default']['default']);
}

/**
 * Sets up the test runs results storage.
 */
function simpletest_script_setup_test_run_results_storage($new = FALSE) {
  global $args;

  $databases['default'] = Database::getConnectionInfo('default');

  // If no --sqlite parameter has been passed, then the test runner database
  // connection is the default database connection.
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
    $databases['test-runner']['default'] = [
      'driver' => 'sqlite',
      'database' => $sqlite,
      'prefix' => '',
    ];
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

  // Create the test result schema.
  try {
    $test_run_results_storage = new SimpletestTestRunResultsStorage(Database::getConnection('default', 'test-runner'));
  }
  catch (\PDOException $e) {
    simpletest_script_print_error($databases['test-runner']['default']['driver'] . ': ' . $e->getMessage());
    exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
  }
  if ($new && $sqlite) {
    try {
      $test_run_results_storage->buildTestingResultsEnvironment(!empty($args['keep-results-table']));
    }
    catch (Exception $e) {
      echo (string) $e;
      exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
    }
  }
  // Verify that the test result database schema exists by checking one table.
  try {
    if (!$test_run_results_storage->validateTestingResultsEnvironment()) {
      simpletest_script_print_error('Missing test result database schema. Use the --sqlite parameter.');
      exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
    }
  }
  catch (Exception $e) {
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }

  return $test_run_results_storage;
}

/**
 * Execute a batch of tests.
 */
function simpletest_script_execute_batch(TestRunResultsStorageInterface $test_run_results_storage, $test_classes) {
  global $args, $test_ids;

  $total_status = SIMPLETEST_SCRIPT_EXIT_SUCCESS;

  // Multi-process execution.
  $children = [];
  while (!empty($test_classes) || !empty($children)) {
    while (count($children) < $args['concurrency']) {
      if (empty($test_classes)) {
        break;
      }

      try {
        $test_run = TestRun::createNew($test_run_results_storage);
      }
      catch (Exception $e) {
        echo (string) $e;
        exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
      }
      $test_ids[] = $test_run->id();

      $test_class = array_shift($test_classes);
      // Fork a child process.
      $command = simpletest_script_command($test_run, $test_class);
      $process = proc_open($command, [], $pipes, NULL, NULL, ['bypass_shell' => TRUE]);

      if (!is_resource($process)) {
        echo "Unable to fork test process. Aborting.\n";
        exit(SIMPLETEST_SCRIPT_EXIT_SUCCESS);
      }

      // Register our new child.
      $children[] = [
        'process' => $process,
        'test_run' => $test_run,
        'class' => $test_class,
        'pipes' => $pipes,
      ];
    }

    // Wait for children every 200ms.
    usleep(200000);

    // Check if some children finished.
    foreach ($children as $cid => $child) {
      $status = proc_get_status($child['process']);
      if (empty($status['running'])) {
        // The child exited, unregister it.
        proc_close($child['process']);
        if ($status['exitcode'] === SIMPLETEST_SCRIPT_EXIT_FAILURE) {
          $total_status = max($status['exitcode'], $total_status);
        }
        elseif ($status['exitcode']) {
          $message = 'FATAL ' . $child['class'] . ': test runner returned a non-zero error code (' . $status['exitcode'] . ').';
          echo $message . "\n";
          // @todo Return SIMPLETEST_SCRIPT_EXIT_EXCEPTION instead, when
          // DrupalCI supports this.
          // @see https://www.drupal.org/node/2780087
          $total_status = max(SIMPLETEST_SCRIPT_EXIT_FAILURE, $total_status);
          // Insert a fail for xml results.
          $child['test_run']->insertLogEntry([
            'test_class' => $child['class'],
            'status' => 'fail',
            'message' => $message,
            'message_group' => 'run-tests.sh check',
          ]);
          // Ensure that an error line is displayed for the class.
          simpletest_script_reporter_display_summary(
            $child['class'],
            ['#pass' => 0, '#fail' => 1, '#exception' => 0, '#debug' => 0]
          );
          if ($args['die-on-fail']) {
            $test_db = new TestDatabase($child['test_run']->getDatabasePrefix());
            $test_directory = $test_db->getTestSitePath();
            echo 'Test database and files kept and test exited immediately on fail so should be reproducible if you change settings.php to use the database prefix ' . $child['test_run']->getDatabasePrefix() . ' and config directories in ' . $test_directory . "\n";
            $args['keep-results'] = TRUE;
            // Exit repeat loop immediately.
            $args['repeat'] = -1;
          }
        }

        // Remove this child.
        unset($children[$cid]);
      }
    }
  }
  return $total_status;
}

/**
 * Run a PHPUnit-based test.
 */
function simpletest_script_run_phpunit(TestRun $test_run, $class) {
  $reflection = new \ReflectionClass($class);
  if ($reflection->hasProperty('runLimit')) {
    set_time_limit($reflection->getStaticPropertyValue('runLimit'));
  }

  $runner = PhpUnitTestRunner::create(\Drupal::getContainer());
  $results = $runner->execute($test_run, [$class], $status);
  $runner->processPhpUnitResults($test_run, $results);

  $summaries = $runner->summarizeResults($results);
  foreach ($summaries as $class => $summary) {
    simpletest_script_reporter_display_summary($class, $summary);
  }
  return $status;
}

/**
 * Run a single test, bootstrapping Drupal if needed.
 */
function simpletest_script_run_one_test(TestRun $test_run, $test_class) {
  global $args;

  try {
    if ($args['suppress-deprecations']) {
      putenv('SYMFONY_DEPRECATIONS_HELPER=disabled');
    }
    $status = simpletest_script_run_phpunit($test_run, $test_class);
    exit($status);
  }
  // DrupalTestCase::run() catches exceptions already, so this is only reached
  // when an exception is thrown in the wrapping test runner environment.
  catch (Exception $e) {
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }
}

/**
 * Return a command used to run a test in a separate process.
 *
 * @param int $test_id
 *   The current test ID.
 * @param string $test_class
 *   The name of the test class to run.
 *
 * @return string
 *   The assembled command string.
 */
function simpletest_script_command(TestRun $test_run, $test_class) {
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
  $command .= " --test-id {$test_run->id()}";
  foreach (['verbose', 'keep-results', 'color', 'die-on-fail', 'suppress-deprecations'] as $arg) {
    if ($args[$arg]) {
      $command .= ' --' . $arg;
    }
  }
  // --execute-test and class name needs to come last.
  $command .= ' --execute-test ' . escapeshellarg($test_class);
  return $command;
}

/**
 * Get list of tests based on arguments.
 *
 * If --all specified then return all available tests, otherwise reads list of
 * tests.
 *
 * @return array
 *   List of tests.
 */
function simpletest_script_get_test_list() {
  global $args;

  $test_discovery = new TestDiscovery(
    \Drupal::root(),
    \Drupal::service('class_loader')
  );
  $types_processed = empty($args['types']);
  $test_list = [];
  $slow_tests = [];
  if ($args['all'] || $args['module'] || $args['directory']) {
    try {
      $groups = $test_discovery->getTestClasses($args['module'], $args['types'], $args['directory']);
      $types_processed = TRUE;
    }
    catch (Exception $e) {
      echo (string) $e;
      exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
    }
    // If the tests are run in parallel jobs, ensure that slow tests are
    // distributed between each job.
    if ((int) $args['ci-parallel-node-total'] > 1) {
      if (key($groups) === '#slow') {
        $slow_tests = array_keys(array_shift($groups));
      }
    }
    $all_tests = [];
    foreach ($groups as $group => $tests) {
      if ($group === '#slow') {
        $slow_group = $tests;
      }
      else {
        $all_tests = array_merge($all_tests, array_keys($tests));
      }
    }
    // If no type has been set, order the tests alphabetically by test namespace
    // so that unit tests run last. This takes advantage of the fact that Build,
    // Functional, Functional JavaScript, Kernel, Unit roughly corresponds to
    // test time.
    usort($all_tests, function ($a, $b) {
      $slice = function ($class) {
        $parts = explode('\\', $class);
        return implode('\\', array_slice($parts, 3));
      };
      return $slice($a) > $slice($b) ? 1 : -1;
    });
    // If the tests are not being run in parallel, then ensure slow tests run all
    // together first.
    if ((int) $args['ci-parallel-node-total'] <= 1 && !empty($slow_group)) {
      $all_tests = array_merge(array_keys($slow_group), $all_tests);
    }
    $test_list = array_unique($all_tests);
    $test_list = array_diff($test_list, $slow_tests);
  }
  else {
    if ($args['class']) {
      $test_list = [];
      foreach ($args['test_names'] as $test_class) {
        [$class_name] = explode('::', $test_class, 2);
        if (class_exists($class_name)) {
          $test_list[] = $test_class;
        }
        else {
          try {
            $groups = $test_discovery->getTestClasses(NULL, $args['types']);
          }
          catch (Exception $e) {
            echo (string) $e;
            exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
          }
          $all_classes = [];
          foreach ($groups as $group) {
            $all_classes = array_merge($all_classes, array_keys($group));
          }
          simpletest_script_print_error('Test class not found: ' . $class_name);
          simpletest_script_print_alternatives($class_name, $all_classes, 6);
          exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
        }
      }
    }
    elseif ($args['file']) {
      // Extract test case class names from specified files.
      $parser = new TestFileParser();
      foreach ($args['test_names'] as $file) {
        if (!file_exists($file)) {
          simpletest_script_print_error('File not found: ' . $file);
          exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
        }
        $test_list = array_merge($test_list, $parser->getTestListFromFile($file));
      }
    }
    else {
      try {
        $groups = $test_discovery->getTestClasses(NULL, $args['types']);
        $types_processed = TRUE;
      }
      catch (Exception $e) {
        echo (string) $e;
        exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
      }
      // Store all the groups so we can suggest alternatives if we need to.
      $all_groups = array_keys($groups);
      // Verify that the groups exist.
      if (!empty($unknown_groups = array_diff($args['test_names'], $all_groups))) {
        $first_group = reset($unknown_groups);
        simpletest_script_print_error('Test group not found: ' . $first_group);
        simpletest_script_print_alternatives($first_group, $all_groups);
        exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
      }
      // Merge the tests from the groups together.
      foreach ($args['test_names'] as $group_name) {
        $test_list = array_merge($test_list, array_keys($groups[$group_name]));
      }
      // Ensure our list of tests contains only one entry for each test.
      $test_list = array_unique($test_list);
    }
  }

  // If the test list creation does not automatically limit by test type then
  // we need to do so here.
  if (!$types_processed) {
    $test_list = array_filter($test_list, function ($test_class) use ($args) {
      $test_info = TestDiscovery::getTestInfo($test_class);
      return in_array($test_info['type'], $args['types'], TRUE);
    });
  }

  if (empty($test_list)) {
    simpletest_script_print_error('No valid tests were specified.');
    exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
  }

  if ((int) $args['ci-parallel-node-total'] > 1) {
    $slow_tests_per_job = ceil(count($slow_tests) / $args['ci-parallel-node-total']);
    $tests_per_job = ceil(count($test_list) / $args['ci-parallel-node-total']);
    $test_list = array_merge(array_slice($slow_tests, ($args['ci-parallel-node-index'] -1) * $slow_tests_per_job, $slow_tests_per_job), array_slice($test_list, ($args['ci-parallel-node-index'] - 1) * $tests_per_job, $tests_per_job));
  }

  return $test_list;
}

/**
 * Initialize the reporter.
 */
function simpletest_script_reporter_init() {
  global $args, $test_list, $results_map;

  $results_map = [
    'pass' => 'Pass',
    'fail' => 'Fail',
    'exception' => 'Exception',
  ];

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
  $output = vsprintf('%-60.60s %10s %9s %14s %12s', [
    $class,
    $results['#pass'] . ' passes',
    !$results['#fail'] ? '' : $results['#fail'] . ' fails',
    !$results['#exception'] ? '' : $results['#exception'] . ' exceptions',
    !$results['#debug'] ? '' : $results['#debug'] . ' messages',
  ]);

  $status = ($results['#fail'] || $results['#exception'] ? 'fail' : 'pass');
  simpletest_script_print($output . "\n", simpletest_script_color_code($status));
}

/**
 * Display jUnit XML test results.
 */
function simpletest_script_reporter_write_xml_results(TestRunResultsStorageInterface $test_run_results_storage) {
  global $args, $test_ids, $results_map;

  try {
    $results = simpletest_script_load_messages_by_test_id($test_run_results_storage, $test_ids);
  }
  catch (Exception $e) {
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }

  $test_class = '';
  $xml_files = [];

  foreach ($results as $result) {
    if (isset($results_map[$result->status])) {
      if ($result->test_class != $test_class) {
        // We've moved onto a new class, so write the last classes results to a
        // file:
        if (isset($xml_files[$test_class])) {
          file_put_contents($args['xml'] . '/' . str_replace('\\', '_', $test_class) . '.xml', $xml_files[$test_class]['doc']->saveXML());
          unset($xml_files[$test_class]);
        }
        $test_class = $result->test_class;
        if (!isset($xml_files[$test_class])) {
          $doc = new DomDocument('1.0');
          $root = $doc->createElement('testsuite');
          $root = $doc->appendChild($root);
          $xml_files[$test_class] = ['doc' => $doc, 'suite' => $root];
        }
      }

      // For convenience:
      $dom_document = &$xml_files[$test_class]['doc'];

      // Create the XML element for this test case:
      $case = $dom_document->createElement('testcase');
      $case->setAttribute('classname', $test_class);
      if (str_contains($result->function, '->')) {
        [$class, $name] = explode('->', $result->function, 2);
      }
      else {
        $name = $result->function;
      }
      $case->setAttribute('name', $name);

      // Passes get no further attention, but failures and exceptions get to add
      // more detail:
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
  echo "Test run duration: " . \Drupal::service('date.formatter')->formatInterval((int) ($end['time'] / 1000));
  echo "\n\n";
}

/**
 * Display test results.
 */
function simpletest_script_reporter_display_results(TestRunResultsStorageInterface $test_run_results_storage) {
  global $args, $test_ids, $results_map;

  if ($args['verbose']) {
    // Report results.
    echo "Detailed test results\n";
    echo "---------------------\n";

    try {
      $results = simpletest_script_load_messages_by_test_id($test_run_results_storage, $test_ids);
    }
    catch (Exception $e) {
      echo (string) $e;
      exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
    }
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
 * Format the result so that it fits within 80 characters.
 *
 * @param object $result
 *   The result object to format.
 */
function simpletest_script_format_result($result) {
  global $args, $results_map, $color;

  $summary = sprintf("%-9.9s %-10.10s %-17.17s %4.4s %-35.35s\n",
    $results_map[$result->status], $result->message_group, basename($result->file), $result->line, $result->function);

  simpletest_script_print($summary, simpletest_script_color_code($result->status));

  $message = trim(strip_tags($result->message));
  if ($args['non-html']) {
    $message = Html::decodeEntities($message);
  }
  $lines = explode("\n", wordwrap($message), 76);
  foreach ($lines as $line) {
    echo "    $line\n";
  }
}

/**
 * Print error messages so the user will notice them.
 *
 * Print error message prefixed with "  ERROR: " and displayed in fail color if
 * color output is enabled.
 *
 * @param string $message
 *   The message to print.
 */
function simpletest_script_print_error($message) {
  simpletest_script_print("  ERROR: $message\n", SIMPLETEST_SCRIPT_COLOR_FAIL);
}

/**
 * Print a message to the console, using a color.
 *
 * @param string $message
 *   The message to print.
 * @param int $color_code
 *   The color code to use for coloring.
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
 * @param string $status
 *   The status string to get code for. Special cases are: 'pass', 'fail', or
 *   'exception'.
 *
 * @return int
 *   Color code. Returns 0 for default case.
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
  // Default formatting.
  return 0;
}

/**
 * Prints alternative test names.
 *
 * Searches the provided array of string values for close matches based on the
 * Levenshtein algorithm.
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
 *
 * @see http://php.net/manual/function.levenshtein.php
 */
function simpletest_script_print_alternatives($string, $array, $degree = 4) {
  $alternatives = [];
  foreach ($array as $item) {
    $lev = levenshtein($string, $item);
    if ($lev <= strlen($item) / $degree || str_contains($string, $item)) {
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
 * Loads test result messages from the database.
 *
 * Messages are ordered by test class and message id.
 *
 * @param array $test_ids
 *   Array of test IDs of the messages to be loaded.
 *
 * @return array
 *   Array of test result messages from the database.
 */
function simpletest_script_load_messages_by_test_id(TestRunResultsStorageInterface $test_run_results_storage, $test_ids) {
  global $args;
  $results = [];

  // Sqlite has a maximum number of variables per query. If required, the
  // database query is split into chunks.
  if (count($test_ids) > SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT && !empty($args['sqlite'])) {
    $test_id_chunks = array_chunk($test_ids, SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT);
  }
  else {
    $test_id_chunks = [$test_ids];
  }

  foreach ($test_id_chunks as $test_id_chunk) {
    try {
      $result_chunk = [];
      foreach ($test_id_chunk as $test_id) {
        $test_run = TestRun::get($test_run_results_storage, $test_id);
        $result_chunk = array_merge($result_chunk, $test_run->getLogEntriesByTestClass());
      }
    }
    catch (Exception $e) {
      echo (string) $e;
      exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
    }
    if ($result_chunk) {
      $results = array_merge($results, $result_chunk);
    }
  }

  return $results;
}
