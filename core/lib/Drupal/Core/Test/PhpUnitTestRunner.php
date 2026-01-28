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
   * Path to PHPUnit's configuration file.
   */
  private string $configurationFilePath;

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
   * Sets the configuration file path.
   */
  public function setConfigurationFilePath(string $configurationFilePath): self {
    $this->configurationFilePath = $configurationFilePath;
    return $this;
  }

  /**
   * Starts asynchronous execution of a PHPUnit process.
   *
   * This method executes the tests for a single test class.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param class-string $test_class
   *   A fully qualified test class name.
   * @param bool $colors
   *   Whether to use colors in output.
   * @param bool $suppressDeprecations
   *   If TRUE, disables the deprecation reporting.
   *
   * @return \Symfony\Component\Process\Process
   *   The spawned process object.
   */
  public function startPhpUnitOnSingleTestClass(
    TestRun $test_run,
    string $test_class,
    bool $colors,
    bool $suppressDeprecations,
  ): Process {
    $command = $this->prepareCommand($test_run, $test_class, $colors, $suppressDeprecations);
    $test_run->start(microtime(TRUE));
    $process = new Process($command['cli_command'], $command['work_dir'], $command['environment_vars']);
    $process->setTimeout(NULL);
    $process->start();
    return $process;
  }

  /**
   * Processes PHPUnit test execution output.
   *
   * @param \Symfony\Component\Process\Process $process
   *   The PHPUnit CLI process - terminated.
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param class-string $test_class
   *   A fully qualified test class name.
   *
   * @return array
   *   The results.
   */
  public function processPhpUnitOnSingleTestClassOutcome(
    Process $process,
    TestRun $test_run,
    string $test_class,
  ): array {
    if (!$process->isTerminated()) {
      throw new \RuntimeException('An error occurred: subprocess was not terminated before starting processing its output');
    }

    $out = $process->getOutput();
    $err = $process->getErrorOutput();

    $output = explode("\n", $out);
    $errorOutput = $err;
    if (!empty($errorOutput)) {
      $error = explode("\n", $err);
    }
    $status = $process->getExitCode();

    $log_junit_file_path = $this->xmlLogFilePath($test_run->id());
    if (file_exists($log_junit_file_path)) {
      $phpunit_results = JUnitConverter::xmlToRows($test_run->id(), $log_junit_file_path);
    }
    else {
      $phpunit_results = [];
    }

    // If not passed, add full PHPUnit run output since individual test cases
    // messages may not give full clarity (deprecations, warnings, etc.).
    if ($status > TestStatus::PASS) {
      $message = $out;
      if (!empty($error)) {
        $message .= "\nERROR:\n";
        $message .= $err;
      }
      $phpunit_results[] = [
        'test_id' => $test_run->id(),
        'test_class' => $test_class,
        'status' => $status < TestStatus::SYSTEM ? 'cli_fail' : 'exception',
        'exit_code' => $status,
        'message' => $message,
        'message_group' => 'Other',
        'function' => '*** Process execution output ***',
        'line' => '0',
        'file' => $log_junit_file_path,
        'time' => 0,
      ];
    }

    $this->processPhpUnitResults($test_run, $phpunit_results);
    $summaries = $this->summarizeResults($phpunit_results);

    return [
      'status' => $status,
      'output' => $output,
      'error_output' => $error ?? NULL,
      'phpunit_results' => $phpunit_results,
      'summaries' => $summaries,
    ];
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
   * Prepares the context to execute a PHPUnit process.
   *
   * This method prepares the command line arguments necessary to execute
   * the tests for a single test class, as well as the necessary context of
   * working directory and environment variables.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param class-string $test_class_name
   *   A fully qualified test class name.
   * @param bool $colors
   *   Whether to use colors in output.
   * @param bool $suppressDeprecations
   *   If TRUE, disables the deprecation reporting.
   *
   * @return array{cli_command: list<string>, work_dir: string, environment_vars: array<string,string>}
   *   The process execution context.
   */
  protected function prepareCommand(
    TestRun $test_run,
    string $test_class_name,
    bool $colors = FALSE,
    bool $suppressDeprecations = FALSE,
  ): array {
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

    // Determine the JUnit file path.
    $log_junit_file_path = $this->xmlLogFilePath($test_run->id());

    // PHPUnit whereabouts.
    $phpunit_bin = $this->phpUnitCommand();

    // Build the command line for the PHPUnit CLI invocation.
    $command = [
      $phpunit_bin,
      '--configuration',
      $this->configurationFilePath,
      '--testdox',
      '--log-junit',
      $log_junit_file_path,
    ];
    if ($colors) {
      $command[] = '--colors=always';
    }

    if ($suppressDeprecations) {
      $process_environment_variables['SYMFONY_DEPRECATIONS_HELPER'] = 'disabled';
    }
    else {
      // If the deprecation handler bridge is active, we need to fail when there
      // are deprecations that get reported (i.e. not ignored or expected).
      $deprecationConfiguration = DeprecationHandler::getConfiguration();
      if ($deprecationConfiguration !== FALSE) {
        $command[] = '--fail-on-deprecation';
        if ($deprecationConfiguration['failOnPhpunitDeprecation']) {
          $command[] = '--fail-on-phpunit-deprecation';
        }
      }
    }

    // Add to the command the file containing the test class to be run.
    $reflectedClass = new \ReflectionClass($test_class_name);
    $command[] = $reflectedClass->getFileName();

    return [
      'cli_command' => $command,
      'work_dir' => DRUPAL_ROOT . "/core",
      'environment_vars' => $process_environment_variables,
    ];
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
      if (!$test_run->insertLogEntry($result)) {
        throw new \RuntimeException('Failed insertion of a test log entry');
      }
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
          '#cli_fail' => 0,
          '#exception' => 0,
          '#debug' => 0,
          '#time' => 0,
          '#exit_code' => 0,
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

        case 'cli_fail':
          $summaries[$result['test_class']]['#cli_fail']++;
          $summaries[$result['test_class']]['#exit_code'] = max($summaries[$result['test_class']]['#exit_code'], $result['exit_code']);
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
