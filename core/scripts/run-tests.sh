<?php

/**
 * @file
 * Script for running tests on DrupalCI.
 *
 * This script is intended for use only by drupal.org testing. In general,
 * tests should be run directly with phpunit.
 *
 * @internal
 */

use Composer\Autoload\ClassLoader;
use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Composer\Composer;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\EnvironmentCleaner;
use Drupal\Core\Test\PhpUnitTestDiscovery;
use Drupal\Core\Test\PhpUnitTestRunner;
use Drupal\Core\Test\SimpletestTestRunResultsStorage;
use Drupal\Core\Test\TestDatabase;
use Drupal\Core\Test\TestRun;
use Drupal\Core\Test\TestRunnerKernel;
use Drupal\Core\Test\TestRunResultsStorageInterface;
use Drupal\TestTools\TestRunner\Configuration as Config;
use Drupal\TestTools\TestRunner\WorkAllocator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\PhpExecutableFinder;

// cspell:ignore exitcode testbots wwwrun

// Define some colors for display.
// A nice calming green.
const SIMPLETEST_SCRIPT_COLOR_PASS = 32;
// An alerting Red.
const SIMPLETEST_SCRIPT_COLOR_FAIL = 31;
// An annoying brown.
const SIMPLETEST_SCRIPT_COLOR_EXCEPTION = 33;
// An appeasing yellow.
const SIMPLETEST_SCRIPT_COLOR_YELLOW = 33;
// A refreshing cyan.
const SIMPLETEST_SCRIPT_COLOR_CYAN = 36;
// A fainting gray.
const SIMPLETEST_SCRIPT_COLOR_GRAY = 90;
// A notable white.
const SIMPLETEST_SCRIPT_COLOR_BRIGHT_WHITE = "1;97";

// Restricting the chunk of queries prevents memory exhaustion.
const SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT = 350;

const SIMPLETEST_SCRIPT_EXIT_SUCCESS = 0;
const SIMPLETEST_SCRIPT_EXIT_FAILURE = 1;
const SIMPLETEST_SCRIPT_EXIT_ERROR = 2;
const SIMPLETEST_SCRIPT_EXIT_EXCEPTION = 3;

// Setup class autoloading.
$autoloader = require_once __DIR__ . '/../../autoload.php';
$autoloader->addPsr4('Drupal\\TestTools\\', __DIR__ . '/../tests/Drupal/TestTools');

// Setup console output.
$console_output = new ConsoleOutput();

// Get the configuration from the command line.
$script_basename = basename($_SERVER['argv'][0]);
try {
  Config::createFromCommandLine($_SERVER['argv']);
}
catch (\RuntimeException $e) {
  simpletest_script_print_error($e->getMessage() . ' ' . "Use the --help option for the list and usage of the options available.\n");
  simpletest_script_print(Config::commandLineDefinition()->getSynopsis(), SIMPLETEST_SCRIPT_COLOR_PASS);
  exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
}

// If --help requested, show it and exit.
if (Config::get('help')) {
  simpletest_script_help(Config::commandLineDefinition(), $script_basename, $console_output);
  exit(SIMPLETEST_SCRIPT_EXIT_SUCCESS);
}

// Initialize script variables and bootstrap Drupal kernel.
simpletest_script_init($autoloader);

if (!class_exists(TestCase::class)) {
  echo "\nrun-tests.sh requires the PHPUnit testing framework. Use 'composer install' to ensure that it is present.\n\n";
  exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
}

// Defaults the PHPUnit configuration file path.
if (empty(Config::get('phpunit-configuration'))) {
  Config::set('phpunit-configuration', \Drupal::root() . \DIRECTORY_SEPARATOR . 'core');
}

if (!Composer::upgradePHPUnitCheck(Version::id())) {
  simpletest_script_print_error("PHPUnit testing framework version 11 or greater is required when running on PHP 8.4 or greater. Run the command 'composer run-script drupal-phpunit-upgrade' in order to fix this.");
  exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
}

if (Config::get('list')) {
  // Display all available tests organized by one #[Group()] attribute.
  echo "\nAvailable test groups & classes\n";
  echo "-------------------------------\n\n";
  $testDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath(Config::get('phpunit-configuration'));
  try {
    $groupedTestClassInfoList = $testDiscovery->getTestClasses(Config::get('module'), Config::get('types'), Config::get('directory'), Config::getTests());
    dump_discovery_warnings();
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
  foreach ($groupedTestClassInfoList as $group => $tests) {
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
if (Config::get('list-files') || Config::get('list-files-json')) {
  // List all files which could be run as tests.
  $testDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath(Config::get('phpunit-configuration'));
  // PhpUnitTestDiscovery::findAllClassFiles() gives us a classmap similar to a
  // Composer 'classmap' array.
  $test_classes = $testDiscovery->findAllClassFiles(Config::get('module'), Config::get('types'), Config::get('directory'), Config::getTests());
  // JSON output is the easiest.
  if (Config::get('list-files-json')) {
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

simpletest_script_setup_database();

// Setup the test run results storage environment. Currently, this coincides
// with the simpletest database schema.
$test_run_results_storage = simpletest_script_setup_test_run_results_storage();

if (Config::get('clean')) {
  // Clean up left-over tables and directories.
  $cleaner = new EnvironmentCleaner(
    DRUPAL_ROOT,
    Database::getConnection(),
    $test_run_results_storage,
    $console_output,
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

echo "\n";
echo "Drupal test run\n\n";
echo "--------------------------------------------------------------\n";
echo sprintf("Drupal Version.......: %s\n", \Drupal::VERSION);
echo sprintf("PHP Version..........: %s\n", \PHP_VERSION);
echo sprintf("PHP Binary...........: %s\n", (new PhpExecutableFinder())->find());
echo sprintf("PHPUnit Version......: %s\n", Version::id());
echo sprintf("PHPUnit configuration: %s\n", Config::get('phpunit-configuration'));
if (Config::get('dburl')) {
  $sut_connection_info = Database::getConnectionInfo();
  $sut_tasks_class = $sut_connection_info['default']['namespace'] . "\\Install\\Tasks";
  $sut_installer = new $sut_tasks_class();
  $sut_connection = Database::getConnection();
  echo sprintf("Database.............: %s\n", (string) $sut_installer->name());
  echo sprintf("Database Version.....: %s\n", $sut_connection->version());
}
echo sprintf("Working directory....: %s\n", getcwd());
echo "--------------------------------------------------------------\n";

$groupedTestClassInfoList = simpletest_script_get_test_list();

$workAllocator = new WorkAllocator(
  $groupedTestClassInfoList,
  (int) Config::get('ci-parallel-node-total'),
  (int) Config::get('ci-parallel-node-index'),
);
$test_list = array_keys($workAllocator->getAllocatedList());

if (Config::get('debug-discovery')) {
  if ((int) Config::get('ci-parallel-node-total') > 1) {
    dump_bin_tests_sequence((int) Config::get('ci-parallel-node-index'), $workAllocator->getSortedList(), $workAllocator->getAllocatedList());
  }
  else {
    dump_tests_sequence($workAllocator->getAllocatedList());
  }
}

// Try to allocate unlimited time to run the tests.
Environment::setTimeLimit(0);
simpletest_script_reporter_init();

$tests_to_run = [];
for ($i = 0; $i < Config::get('repeat'); $i++) {
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

if (Config::get('xml')) {
  simpletest_script_reporter_write_xml_results($test_run_results_storage);
}

// Clean up all test results.
if (!Config::get('keep-results')) {
  try {
    $cleaner = new EnvironmentCleaner(
      DRUPAL_ROOT,
      Database::getConnection(),
      $test_run_results_storage,
      $console_output,
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
function simpletest_script_help(InputDefinition $input_definition, string $script_basename, ConsoleOutput $console_output): void {

  echo <<<EOF

Run Drupal tests from the shell.

Usage:        {$script_basename} [OPTIONS] <tests>
Example:      {$script_basename} Profile


EOF;

  $helper = new DescriptorHelper();
  $helper->describe($console_output, $input_definition);

  echo <<<EOF


To run this script you will normally invoke it from the root directory of your
Drupal installation as the webserver user (differs per configuration), or root:

sudo -u [wwwrun|www-data|etc] php ./core/scripts/{$script_basename} --url http://example.com/ --all

sudo -u [wwwrun|www-data|etc] php ./core/scripts/{$script_basename} --url http://example.com/ --class Drupal\\\\Tests\\\\block\\\\Functional\\\\BlockTest

Without a preinstalled Drupal site, specify a SQLite database pathname to create
(for the test runner) and the default database connection info (for Drupal) to
use in tests:

sudo -u [wwwrun|www-data|etc] php ./core/scripts/{$script_basename}
  --sqlite /tmpfs/drupal/test.sqlite
  --dburl mysql://username:password@localhost/database
  --url http://example.com/ --all


EOF;
}

/**
 * Initialize script variables and perform general setup requirements.
 *
 * @param \Drupal\Core\Composer\Composer $autoloader
 *   The Composer provided PHP class loader.
 */
function simpletest_script_init(ClassLoader $autoloader): void {
  // Get URL from arguments.
  $parsed_url = parse_url(Config::get('url'));
  $host = $parsed_url['host'] . (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '');
  $path = isset($parsed_url['path']) ? rtrim(rtrim($parsed_url['path']), '/') : '';
  $port = $parsed_url['port'] ?? '80';

  // If the passed URL schema is 'https' then setup the $_SERVER variables
  // properly so that testing will run under HTTPS.
  if ($parsed_url['scheme'] == 'https') {
    $_SERVER['HTTPS'] = 'on';
  }

  $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
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

  if (Config::get('concurrency') > 1) {
    $directory = FileSystem::getOsTemporaryDirectory();
    $test_symlink = @symlink(__FILE__, $directory . '/test_symlink');
    if (!$test_symlink) {
      throw new \RuntimeException('In order to use a concurrency higher than 1 the test system needs to be able to create symlinks in ' . $directory);
    }
    unlink($directory . '/test_symlink');
    putenv('RUN_TESTS_CONCURRENCY=' . Config::get('concurrency'));
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
 */
function simpletest_script_setup_database(): void {
  // If there is an existing Drupal installation that contains a database
  // connection info in settings.php, then $databases['default']['default'] will
  // hold the default database connection already. This connection is assumed to
  // be valid, and this connection will be used in tests, so that they run
  // against e.g. MySQL instead of SQLite.
  // However, in case no Drupal installation exists, this default database
  // connection can be set and/or overridden with the --dburl parameter.
  if (Config::get('dburl')) {
    // Remove a possibly existing default connection (from settings.php).
    Database::removeConnection('default');
    try {
      $databases['default']['default'] = Database::convertDbUrlToConnectionInfo(Config::get('dburl'), TRUE);
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

  if (isset($databases['default']['default'])) {
    Database::addConnectionInfo('default', 'default', $databases['default']['default']);
  }
}

/**
 * Sets up the test runs results storage.
 */
function simpletest_script_setup_test_run_results_storage() {
  $databases['default'] = Database::getConnectionInfo('default');

  // If no --sqlite parameter has been passed, then the test runner database
  // connection is the default database connection.
  $sqlite = Config::get('sqlite');
  if (!$sqlite) {
    $sqlite = FALSE;
    $databases['test-runner']['default'] = $databases['default']['default'];
  }
  // Otherwise, set up a SQLite connection for the test runner.
  else {
    if ($sqlite === ':memory:') {
      $sqlite = ':memory:';
    }
    elseif (is_string($sqlite) && !str_starts_with($sqlite, '/')) {
      $sqlite = DRUPAL_ROOT . '/' . $sqlite;
    }
    $databases['test-runner']['default'] = [
      'driver' => 'sqlite',
      'database' => $sqlite,
      'prefix' => '',
    ];
    // Create the test runner SQLite database, unless it exists already.
    if ($sqlite !== ':memory:' && !file_exists($sqlite)) {
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
  if ($sqlite) {
    try {
      $test_run_results_storage->buildTestingResultsEnvironment(Config::get('keep-results-table'));
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
  global $test_ids, $total_time;

  $total_status = SIMPLETEST_SCRIPT_EXIT_SUCCESS;

  $process_runner = PhpUnitTestRunner::create(\Drupal::getContainer())
    ->setConfigurationFilePath(Config::get('phpunit-configuration'));

  // Multi-process execution.
  $children = [];
  while (!empty($test_classes) || !empty($children)) {
    while (count($children) < Config::get('concurrency')) {
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
      try {
        $process = $process_runner->startPhpUnitOnSingleTestClass(
          $test_run,
          $test_class,
          Config::get('color'),
          Config::get('suppress-deprecations'),
        );
      }
      catch (\Throwable $e) {
        // PHPUnit catches exceptions already, so this is only reached when an
        // exception is thrown in the wrapped test runner environment.
        echo (string) $e;
        exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
      }

      // Register our new child.
      $children[] = [
        'process' => $process,
        'test_run' => $test_run,
        'class' => $test_class,
      ];
    }

    // Wait for children every 2ms.
    usleep(2000);

    // Check if some children finished.
    foreach ($children as $cid => $child) {
      if ($child['process']->isTerminated()) {
        // The child exited.
        $child['test_run']->end(microtime(TRUE));
        $process_outcome = $process_runner->processPhpUnitOnSingleTestClassOutcome(
          $child['process'],
          $child['test_run'],
          $child['class'],
        );
        simpletest_script_reporter_display_summary(
          $child['class'],
          $process_outcome['summaries'][$child['class']],
          $child['test_run']->duration()
        );
        if ($process_outcome['error_output']) {
          echo 'ERROR: ' . implode("\n", $process_outcome['error_output']);
        }
        if (in_array($process_outcome['status'], [SIMPLETEST_SCRIPT_EXIT_FAILURE, SIMPLETEST_SCRIPT_EXIT_ERROR])) {
          $total_status = max($process_outcome['status'], $total_status);
        }
        elseif ($process_outcome['status']) {
          $message = 'FATAL ' . $child['class'] . ': test runner returned an unexpected error code (' . $process_outcome['status'] . ').';
          echo $message . "\n";
          $total_status = max(SIMPLETEST_SCRIPT_EXIT_EXCEPTION, $total_status);
          if (Config::get('die-on-fail')) {
            $test_db = new TestDatabase($child['test_run']->getDatabasePrefix());
            $test_directory = $test_db->getTestSitePath();
            echo 'Test database and files kept and test exited immediately on fail so should be reproducible if you change settings.php to use the database prefix ' . $child['test_run']->getDatabasePrefix() . ' and config directories in ' . $test_directory . "\n";
            Config::set('keep-results', TRUE);
            // Exit repeat loop immediately.
            Config::set('repeat', -1);
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
 * Get list of tests based on arguments.
 *
 * If --all specified then return all available tests, otherwise reads list of
 * tests.
 *
 * @return array
 *   List of tests.
 */
function simpletest_script_get_test_list() {
  $testDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath(Config::get('phpunit-configuration'));

  echo "Test discovery\n";

  try {
    if (Config::get('all') || Config::get('module') || Config::get('directory')) {
      if (Config::get('types')) {
        echo sprintf("PHPUnit test suite(s): %s\n", implode(', ', Config::get('types')));
      }
      if (Config::get('module')) {
        echo sprintf("Drupal module........: %s\n", Config::get('module'));
      }
      if (Config::get('directory')) {
        echo sprintf("Directory............: %s\n", Config::get('directory'));
      }
      if (Config::getTests()) {
        echo sprintf("PHPUnit test group(s): %s\n", implode(', ', Config::getTests()));
      }
      echo "--------------------------------------------------------------\n";
      $groupedTestClassInfoList = $testDiscovery->getTestClasses(Config::get('module'), Config::get('types'), Config::get('directory'), Config::getTests());
    }
    elseif (Config::get('class')) {
      // When --class is specified, we have to find the file of each of the
      // classes indicated as argument and run test discovery for it, then
      // merge the results.
      $classesArg = Config::getTests();
      echo sprintf("Test class(es).......: %s\n", array_shift($classesArg));
      foreach ($classesArg as $arg) {
        echo sprintf("                     : %s\n", $arg);
      }
      echo "--------------------------------------------------------------\n";
      $groupedTestClassInfoList = [];
      foreach (Config::getTests() as $test_class) {
        [$class_name] = explode('::', $test_class, 2);
        if (class_exists($class_name)) {
          $fileName = (new \ReflectionClass($class_name))->getFileName();
          $groupedClassInfo = $testDiscovery->getTestClasses(NULL, [], $fileName);
          foreach (array_keys($groupedClassInfo) as $classGroupKey) {
            if (array_key_exists($classGroupKey, $groupedTestClassInfoList)) {
              $groupedTestClassInfoList[$classGroupKey] = array_merge($groupedTestClassInfoList[$classGroupKey], $groupedClassInfo[$classGroupKey]);
            }
            else {
              $groupedTestClassInfoList[$classGroupKey] = $groupedClassInfo[$classGroupKey];
            }
          }
        }
        else {
          // The class does not exist: we discover all the test classes and
          // suggest a possible alternative.
          $groupedTestClassInfoList = $testDiscovery->getTestClasses(NULL, Config::get('types'));
          dump_discovery_warnings();
          $all_classes = [];
          foreach ($groupedTestClassInfoList as $group) {
            $all_classes = array_merge($all_classes, array_keys($group));
          }
          simpletest_script_print_error('Test class not found: ' . $class_name);
          simpletest_script_print_alternatives($class_name, $all_classes, 6);
          exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
        }
      }
    }
    elseif (Config::get('file')) {
      // When --file is specified, we have to run test discovery for each of
      // the files indicated, then merge the results.
      $filesArg = Config::getTests();
      echo sprintf("Test file(s).........: %s\n", array_shift($filesArg));
      foreach ($filesArg as $arg) {
        echo sprintf("                     : %s\n", $arg);
      }
      echo "--------------------------------------------------------------\n";
      $groupedTestClassInfoList = [];
      foreach (Config::getTests() as $file) {
        if (!file_exists($file) || is_dir($file)) {
          simpletest_script_print_error('File not found: ' . $file);
          exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
        }
        $groupedClassInfo = $testDiscovery->getTestClasses(NULL, [], $file);
        foreach (array_keys($groupedClassInfo) as $classGroupKey) {
          if (array_key_exists($classGroupKey, $groupedTestClassInfoList)) {
            $groupedTestClassInfoList[$classGroupKey] = array_merge($groupedTestClassInfoList[$classGroupKey], $groupedClassInfo[$classGroupKey]);
          }
          else {
            $groupedTestClassInfoList[$classGroupKey] = $groupedClassInfo[$classGroupKey];
          }
        }
      }
    }
    else {
      // When no restriction options are specified, we consider the argument as
      // a list of groups of tests to be executed.
      if (Config::get('types')) {
        echo sprintf("PHPUnit test suite(s): %s\n", implode(', ', Config::get('types')));
      }
      if (Config::getTests()) {
        echo sprintf("PHPUnit test group(s): %s\n", implode(', ', Config::getTests()));
      }
      echo "--------------------------------------------------------------\n";
      $groupedTestClassInfoList = [];
      try {
        $groupedTestClassInfoFullSuiteList = $testDiscovery->getTestClasses(NULL, Config::get('types'));
      }
      catch (\Exception $e) {
        echo (string) $e;
        exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
      }
      // Store all the groups so we can suggest alternatives if we need to.
      $all_groups = array_keys($groupedTestClassInfoFullSuiteList);
      // Verify that the groups exist.
      if (!empty($unknown_groups = array_diff(Config::getTests(), $all_groups))) {
        $first_group = reset($unknown_groups);
        simpletest_script_print_error('Test group not found: ' . $first_group);
        simpletest_script_print_alternatives($first_group, $all_groups);
        exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
      }
      foreach (Config::getTests() as $group_name) {
        $groupedTestClassInfoList[$group_name] = $groupedTestClassInfoFullSuiteList[$group_name];
      }
      // The '#slow' group is a special case, because it may not be selected in
      // the argument, but it must be present if any test class indicates it in
      // metadata, for the work allocator to prioritize its execution.
      foreach ($groupedTestClassInfoList as $groupName => $testClassInfoList) {
        foreach ($testClassInfoList as $testClass => $testClassInfo) {
          if (in_array('#slow', $testClassInfo['groups'])) {
            $groupedTestClassInfoList['#slow'][$testClass] = $testClassInfo;
          }
        }
      }
    }
  }
  catch (\Exception $e) {
    echo (string) $e;
    exit(SIMPLETEST_SCRIPT_EXIT_EXCEPTION);
  }
  echo "\n";

  dump_discovery_warnings();

  if (empty($groupedTestClassInfoList)) {
    simpletest_script_print_error('No valid tests were specified.');
    exit(SIMPLETEST_SCRIPT_EXIT_FAILURE);
  }

  return $groupedTestClassInfoList;
}

/**
 * Dumps the list of tests in order of execution after sorting.
 *
 * @param array $tests
 *   The array of test class info.
 */
function dump_tests_sequence(array $tests): void {
  if (!Config::get('debug-discovery')) {
    return;
  }
  echo "Test execution sequence\n";
  echo "-----------------------\n\n";
  echo " Seq Slow? Group            Cnt Class\n";
  echo "-----------------------------------------\n";
  foreach ($tests as $testInfo) {
    echo sprintf(
      "%4d %5s %15s %4d %s\n",
      $testInfo['worker_sequence'],
      in_array('#slow', $testInfo['groups']) ? '#slow' : '',
      trim_with_ellipsis($testInfo['group'], 15, \STR_PAD_RIGHT),
      $testInfo['tests_count'],
      trim_with_ellipsis($testInfo['name'], 60, \STR_PAD_LEFT),
    );
  }
  echo "-----------------------------------------\n\n";
}

/**
 * Dumps the list of tests in order of execution for a bin.
 *
 * @param int $bin
 *   The bin.
 * @param array $allTests
 *   The list of all test classes discovered.
 * @param array $tests
 *   The list of test class to run for this bin.
 */
function dump_bin_tests_sequence(int $bin, array $allTests, array $tests): void {
  echo "Test execution sequence. ";
  echo "Tests marked *** will be executed in this PARALLEL BIN #{$bin}.\n";
  echo "-------------------------------------------------------------------------------------\n\n";
  echo "    Sort  Bin                                 \n";
  echo "Bin  Seq  Seq Slow? Group            Cnt Class\n";
  echo "-------------------------------------------------------------------------------------\n";
  foreach ($allTests as $testInfo) {
    $inBin = isset($tests[$testInfo['name']]);
    $message = sprintf(
      "%s %4d %s %5s %15s %4d %s\n",
      $inBin ? "***" : "   ",
      $testInfo['sorted_sequence'],
      $inBin ? sprintf('%4d', $tests[$testInfo['name']]['worker_sequence']) : "    ",
      in_array('#slow', $testInfo['groups']) ? '#slow' : '',
      trim_with_ellipsis($testInfo['group'], 15, \STR_PAD_RIGHT),
      $testInfo['tests_count'],
      trim_with_ellipsis($testInfo['name'], 60, \STR_PAD_LEFT),
    );
    simpletest_script_print($message, $inBin ? SIMPLETEST_SCRIPT_COLOR_BRIGHT_WHITE : SIMPLETEST_SCRIPT_COLOR_GRAY);
  }
  echo "-------------------------------------------------------------------------------------\n\n";
}

/**
 * Initialize the reporter.
 */
function simpletest_script_reporter_init(): void {
  global $test_list, $results_map;

  $results_map = [
    'pass' => 'Pass',
    'fail' => 'Fail',
    'error' => 'Error',
    'skipped' => 'Skipped',
    'cli_fail' => 'Failure',
    'exception' => 'Exception',
    'debug' => 'Log',
  ];

  // Tell the user about what tests are to be run.
  if (Config::get('all')) {
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
 * @param float|null $duration
 *   The time taken for the test to complete.
 */
function simpletest_script_reporter_display_summary($class, $results, $duration = NULL): void {
  // Output all test results vertically aligned.
  $summary = [str_pad($results['#pass'], 4, " ", STR_PAD_LEFT) . ' passed'];
  if ($results['#fail']) {
    $summary[] = $results['#fail'] . ' failed';
  }
  if ($results['#error']) {
    $summary[] = $results['#error'] . ' errored';
  }
  if ($results['#skipped']) {
    $summary[] = $results['#skipped'] . ' skipped';
  }
  if ($results['#exception']) {
    $summary[] = $results['#exception'] . ' exception(s)';
  }
  if ($results['#debug']) {
    $summary[] = $results['#debug'] . ' log(s)';
  }

  if ($results['#cli_fail']) {
    $summary[] = 'exit code ' . $results['#exit_code'];
  }

  // The key $results['#time'] holds the sum of the tests execution times,
  // without taking into account the process spawning time and the setup
  // times of the tests themselves. So for reporting to be consistent with
  // PHPUnit CLI reported execution time, we report here the overall time of
  // execution of the spawned process.
  $time = sprintf('%8.3fs', $duration);

  $output = vsprintf('%s %s %s', [$time, trim_with_ellipsis($class, 70, STR_PAD_LEFT), implode(', ', $summary)]);
  $status = ($results['#fail'] || $results['#cli_fail'] || $results['#exception'] || $results['#error'] ? 'fail' : 'pass');
  simpletest_script_print($output . "\n", simpletest_script_color_code($status));
}

/**
 * Display jUnit XML test results.
 */
function simpletest_script_reporter_write_xml_results(TestRunResultsStorageInterface $test_run_results_storage): void {
  global $test_ids, $results_map;

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
          file_put_contents(Config::get('xml') . '/' . str_replace('\\', '_', $test_class) . '.xml', $xml_files[$test_class]['doc']->saveXML());
          unset($xml_files[$test_class]);
        }
        $test_class = $result->test_class;
        if (!isset($xml_files[$test_class])) {
          $doc = new DOMDocument('1.0', 'utf-8');
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
        [, $name] = explode('->', $result->function, 2);
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
    file_put_contents(Config::get('xml') . '/' . str_replace('\\', '_', $test_class) . '.xml', $xml_files[$test_class]['doc']->saveXML());
    unset($xml_files[$test_class]);
  }
}

/**
 * Stop the test timer.
 */
function simpletest_script_reporter_timer_stop(): void {
  global $total_time;

  echo "\n";
  $end = Timer::stop('run-tests');
  $wall_seconds = $end['time'] / 1000;
  $formatter = \Drupal::service('date.formatter');
  echo "Wall time: " . $formatter->formatInterval((int) $wall_seconds) . "\n";
  echo "Total time:  " . $formatter->formatInterval((int) $total_time) . "\n";
  if ($wall_seconds > 0) {
    echo sprintf("Speedup:   %.2fx (concurrency %d)\n", $total_time / $wall_seconds, Config::get('concurrency'));
  }
  echo "\n";
}

/**
 * Display test results.
 */
function simpletest_script_reporter_display_results(TestRunResultsStorageInterface $test_run_results_storage): void {
  global $test_ids, $results_map;

  if (Config::get('verbose')) {
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
          echo "Status      Duration Info                                                                               \n";
          echo "--------------------------------------------------------------------------------------------------------\n";
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
function simpletest_script_format_result($result): void {
  global $results_map;

  if ($result->time == 0) {
    $duration = "          ";
  }
  elseif ($result->time < 0.001) {
    $duration = "     <1 ms";
  }
  else {
    $duration = sprintf("%9.3fs", $result->time);
  }

  $summary = sprintf("%-9.9s %s %s\n", $results_map[$result->status], $duration, trim_with_ellipsis($result->function, 80, STR_PAD_LEFT));

  simpletest_script_print($summary, simpletest_script_color_code($result->status));

  if ($result->message === '' || in_array($result->status, ['pass', 'fail', 'error'])) {
    return;
  }

  $message = trim(strip_tags($result->message));
  if (Config::get('non-html')) {
    $message = Html::decodeEntities($message);
  }
  $lines = explode("\n", $message);
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
function simpletest_script_print_error($message): void {
  simpletest_script_print("  ERROR: $message\n", SIMPLETEST_SCRIPT_COLOR_FAIL);
}

/**
 * Print a message to the console, using a color.
 *
 * @param string $message
 *   The message to print.
 * @param int|string $color_code
 *   The color code to use for coloring.
 */
function simpletest_script_print($message, $color_code): void {
  try {
    if (Config::get('color')) {
      echo "\033[" . $color_code . "m" . $message . "\033[0m";
    }
    else {
      echo $message;
    }
  }
  catch (\RuntimeException) {
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
  return match ($status) {
    'pass' => SIMPLETEST_SCRIPT_COLOR_PASS,
    'fail', 'cli_fail', 'error', 'exception' => SIMPLETEST_SCRIPT_COLOR_FAIL,
    'skipped' => SIMPLETEST_SCRIPT_COLOR_YELLOW,
    'debug' => SIMPLETEST_SCRIPT_COLOR_CYAN,
    default => 0,
  };
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
function simpletest_script_print_alternatives($string, $array, $degree = 4): void {
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
  $results = [];

  // Sqlite has a maximum number of variables per query. If required, the
  // database query is split into chunks.
  if (count($test_ids) > SIMPLETEST_SCRIPT_SQLITE_VARIABLE_LIMIT && Config::get('sqlite')) {
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

/**
 * Trims a string adding a leading or trailing ellipsis.
 *
 * @param string $input
 *   The input string.
 * @param int $length
 *   The exact trimmed string length.
 * @param int $side
 *   Leading or trailing ellipsis.
 *
 * @return string
 *   The trimmed string.
 */
function trim_with_ellipsis(string $input, int $length, int $side): string {
  if (strlen($input) < $length) {
    return str_pad($input, $length, ' ', \STR_PAD_RIGHT);
  }
  elseif (strlen($input) > $length) {
    return match($side) {
      \STR_PAD_RIGHT => substr($input, 0, $length - 1) . '…',
      default => '…' . substr($input, -$length + 1),
    };
  }
  return $input;
}

/**
 * Outputs the discovery warning messages.
 */
function dump_discovery_warnings(): void {
  $warnings = PhpUnitTestDiscovery::instance()->getWarnings();
  if (!empty($warnings)) {
    simpletest_script_print("Test discovery warnings\n", SIMPLETEST_SCRIPT_COLOR_BRIGHT_WHITE);
    simpletest_script_print("-----------------------\n", SIMPLETEST_SCRIPT_COLOR_BRIGHT_WHITE);
    foreach ($warnings as $warning) {
      $tmp = explode("\n", $warning);
      simpletest_script_print('* ' . array_shift($tmp) . "\n", SIMPLETEST_SCRIPT_COLOR_EXCEPTION);
      foreach ($tmp as $sub) {
        simpletest_script_print('  ' . $sub . "\n", SIMPLETEST_SCRIPT_COLOR_EXCEPTION);
      }
      echo "\n";
    }
  }
}
