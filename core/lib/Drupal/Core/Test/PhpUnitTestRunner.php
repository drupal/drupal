<?php

namespace Drupal\Core\Test;

use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

// cspell:ignore testdox

/**
 * Run PHPUnit-based tests.
 *
 * This class runs PHPUnit-based tests and converts their JUnit results to a
 * format that can be stored in the {simpletest} database schema.
 *
 * This class is internal and not considered to be API.
 *
 * @code
 * $runner = PhpUnitTestRunner::create(\Drupal::getContainer());
 * $results = $runner->execute($test_run, $test_class_name);
 * @endcode
 *
 * @internal
 */
class PhpUnitTestRunner implements ContainerInjectionInterface {

  /**
   * Constructs a test runner.
   *
   * @param string $appRoot
   *   Path to the application root.
   * @param string $workingDirectory
   *   Path to the working directory. JUnit log files will be stored in this
   *   directory.
   */
  public function __construct(
    protected string $appRoot,
    protected string $workingDirectory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      (string) $container->getParameter('app.root'),
      (string) $container->get('file_system')->realpath('public://simpletest')
    );
  }

  /**
   * Returns the path to use for PHPUnit's --log-junit option.
   *
   * @param int $test_id
   *   The current test ID.
   *
   * @return string
   *   Path to the PHPUnit XML file to use for the current $test_id.
   *
   * @internal
   */
  public function xmlLogFilePath(int $test_id): string {
    return $this->workingDirectory . '/phpunit-' . $test_id . '.xml';
  }

  /**
   * Returns the command to run PHPUnit.
   *
   * @return string
   *   The command that can be run through exec().
   *
   * @internal
   */
  public function phpUnitCommand(): string {
    // Load the actual autoloader being used and determine its filename using
    // reflection. We can determine the vendor directory based on that filename.
    $autoloader = require $this->appRoot . '/autoload.php';
    $reflector = new \ReflectionClass($autoloader);
    $vendor_dir = dirname($reflector->getFileName(), 2);

    // The file in Composer's bin dir is a *nix link, which does not work when
    // extracted from a tarball and generally not on Windows.
    $command = $vendor_dir . '/phpunit/phpunit/phpunit';
    if (str_starts_with(PHP_OS, 'WIN')) {
      // On Windows it is necessary to run the script using the PHP executable.
      $php_executable_finder = new PhpExecutableFinder();
      $php = $php_executable_finder->find();
      $command = $php . ' -f ' . escapeshellarg($command) . ' --';
    }
    return $command;
  }

  /**
   * Executes the PHPUnit command.
   *
   * @param string $test_class_name
   *   A fully qualified test class name.
   * @param string $log_junit_file_path
   *   A filepath to use for PHPUnit's --log-junit option.
   * @param int|null $status
   *   (optional) The exit status code of the PHPUnit process will be assigned
   *   to this variable.
   * @param string[]|null $output
   *   (optional) The output by running the phpunit command. If provided, this
   *   array will contain the lines output by the command.
   * @param string[]|null $error
   *   (optional) The error returned by running the phpunit command. If
   *   provided, this array will contain the error lines output by the
   *   command.
   * @param bool $colors
   *   (optional) Whether to use colors in output. Defaults to FALSE.
   *
   * @internal
   */
  protected function runCommand(
    string $test_class_name,
    string $log_junit_file_path,
    ?int &$status = NULL,
    ?array &$output = NULL,
    ?array &$error = NULL,
    bool $colors = FALSE,
  ): void {
    global $base_url;
    $process_environment_variables = [];

    // Setup an environment variable containing the database connection if
    // available, so that non-unit tests can connect to the database.
    try {
      $process_environment_variables['SIMPLETEST_DB'] = Database::getConnectionInfoAsUrl();
    }
    catch (\RuntimeException) {
      // Just continue with no variable set.
    }

    // Setup an environment variable containing the base URL, if it is
    // available. This allows functional tests to browse the site under test.
    // When running tests via CLI, core/phpunit.xml.dist or
    // core/scripts/run-tests.sh can set this variable.
    if ($base_url) {
      $process_environment_variables['SIMPLETEST_BASE_URL'] = $base_url;
      $process_environment_variables['BROWSERTEST_OUTPUT_DIRECTORY'] = $this->workingDirectory;
    }
    $phpunit_bin = $this->phpUnitCommand();

    // Build the command line for the PHPUnit CLI invocation.
    $command = [
      $phpunit_bin,
      '--testdox',
      '--log-junit',
      $log_junit_file_path,
    ];
    if ($colors) {
      $command[] = '--colors=always';
    }

    // If the deprecation handler bridge is active, we need to fail when there
    // are deprecations that get reported (i.e. not ignored or expected).
    $deprecationConfiguration = DeprecationHandler::getConfiguration();
    if ($deprecationConfiguration !== FALSE) {
      $command[] = '--fail-on-deprecation';
      if ($deprecationConfiguration['failOnPhpunitDeprecation']) {
        $command[] = '--fail-on-phpunit-deprecation';
      }
    }

    // Add to the command the file containing the test class to be run.
    $reflectedClass = new \ReflectionClass($test_class_name);
    $command[] = $reflectedClass->getFileName();

    // Invoke PHPUnit CLI with the built command line.
    $process = new Process($command, \Drupal::root() . "/core", $process_environment_variables);
    $process->setTimeout(NULL);
    $process->run();
    $output = explode("\n", $process->getOutput());
    $errorOutput = $process->getErrorOutput();
    if (!empty($errorOutput)) {
      $error = explode("\n", $process->getErrorOutput());
    }
    $status = $process->getExitCode();
  }

  /**
   * Executes PHPUnit tests and returns the results of the run.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param string $test_class_name
   *   A fully qualified test class name.
   * @param int|null $status
   *   (optional) The exit status code of the PHPUnit process will be assigned
   *   to this variable.
   * @param bool $colors
   *   (optional) Whether to use colors in output. Defaults to FALSE.
   *
   * @return array
   *   The parsed results of PHPUnit's JUnit XML output, in the format of
   *   {simpletest}'s schema.
   *
   * @internal
   */
  public function execute(
    TestRun $test_run,
    string $test_class_name,
    ?int &$status = NULL,
    bool $colors = FALSE,
  ): array {
    $log_junit_file_path = $this->xmlLogFilePath($test_run->id());
    // Store output from our test run.
    $output = [];
    $error = [];
    $start = microtime(TRUE);
    $this->runCommand($test_class_name, $log_junit_file_path, $status, $output, $error, $colors);
    $time = microtime(TRUE) - $start;

    if (file_exists($log_junit_file_path)) {
      $results = JUnitConverter::xmlToRows($test_run->id(), $log_junit_file_path);
    }
    else {
      $results = [];
    }

    // If not passed, add full PHPUnit run output since individual test cases
    // messages may not give full clarity (deprecations, warnings, etc.).
    if ($status > TestStatus::PASS) {
      $message = implode("\n", $output);
      if (!empty($error)) {
        $message .= "\nERROR:\n";
        $message .= implode("\n", $error);
      }
      $results[] = [
        'test_id' => $test_run->id(),
        'test_class' => $test_class_name,
        'status' => $status < TestStatus::SYSTEM ? 'debug' : 'exception',
        'message' => $message,
        'message_group' => 'Other',
        'function' => '*** Process execution output ***',
        'line' => '0',
        'file' => $log_junit_file_path,
        'time' => $time,
      ];
    }

    return $results;
  }

  /**
   * Logs the parsed PHPUnit results into the test run.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param array[] $phpunit_results
   *   An array of test results, as returned from
   *   \Drupal\Core\Test\JUnitConverter::xmlToRows(). Can be the return value of
   *   PhpUnitTestRunner::execute().
   */
  public function processPhpUnitResults(TestRun $test_run, array $phpunit_results): void {
    foreach ($phpunit_results as $result) {
      $test_run->insertLogEntry($result);
    }
  }

  /**
   * Tallies test results per test class.
   *
   * @param string[][] $results
   *   Array of results in the {simpletest} schema. Can be the return value of
   *   PhpUnitTestRunner::execute().
   *
   * @return int[][]
   *   Array of status tallies, keyed by test class name and status type.
   *
   * @internal
   */
  public function summarizeResults(array $results): array {
    $summaries = [];
    foreach ($results as $result) {
      if (!isset($summaries[$result['test_class']])) {
        $summaries[$result['test_class']] = [
          '#pass' => 0,
          '#fail' => 0,
          '#error' => 0,
          '#skipped' => 0,
          '#exception' => 0,
          '#debug' => 0,
          '#time' => 0,
        ];
      }

      $summaries[$result['test_class']]['#time'] += $result['time'];

      switch ($result['status']) {
        case 'pass':
          $summaries[$result['test_class']]['#pass']++;
          break;

        case 'fail':
          $summaries[$result['test_class']]['#fail']++;
          break;

        case 'error':
          $summaries[$result['test_class']]['#error']++;
          break;

        case 'skipped':
          $summaries[$result['test_class']]['#skipped']++;
          break;

        case 'exception':
          $summaries[$result['test_class']]['#exception']++;
          break;

        case 'debug':
          $summaries[$result['test_class']]['#debug']++;
          break;

      }
    }
    return $summaries;
  }

}
