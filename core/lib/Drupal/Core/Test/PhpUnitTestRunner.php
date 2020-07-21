<?php

namespace Drupal\Core\Test;

use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Tests\Listeners\SimpletestUiPrinter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Run PHPUnit-based tests.
 *
 * This class runs PHPUnit-based tests and converts their JUnit results to a
 * format that can be stored in the {simpletest} database schema.
 *
 * This class is @internal and not considered to be API.
 *
 * @code
 * $runner = PhpUnitTestRunner::create(\Drupal::getContainer());
 * $results = $runner->runTests($test_id, $test_list['phpunit']);
 * @endcode
 */
class PhpUnitTestRunner implements ContainerInjectionInterface {

  /**
   * Path to the working directory.
   *
   * JUnit log files will be stored in this directory.
   *
   * @var string
   */
  protected $workingDirectory;

  /**
   * Path to the application root.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      (string) $container->getParameter('app.root'),
      (string) $container->get('file_system')->realpath('public://simpletest')
    );
  }

  /**
   * Constructs a test runner.
   *
   * @param string $app_root
   *   Path to the application root.
   * @param string $working_directory
   *   Path to the working directory. JUnit log files will be stored in this
   *   directory.
   */
  public function __construct($app_root, $working_directory) {
    $this->appRoot = $app_root;
    $this->workingDirectory = $working_directory;
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
  public function xmlLogFilePath($test_id) {
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
  public function phpUnitCommand() {
    // Load the actual autoloader being used and determine its filename using
    // reflection. We can determine the vendor directory based on that filename.
    $autoloader = require $this->appRoot . '/autoload.php';
    $reflector = new \ReflectionClass($autoloader);
    $vendor_dir = dirname($reflector->getFileName(), 2);

    // The file in Composer's bin dir is a *nix link, which does not work when
    // extracted from a tarball and generally not on Windows.
    $command = $vendor_dir . '/phpunit/phpunit/phpunit';
    if (substr(PHP_OS, 0, 3) == 'WIN') {
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
   * @param string[] $unescaped_test_classnames
   *   An array of test class names, including full namespaces, to be passed as
   *   a regular expression to PHPUnit's --filter option.
   * @param string $phpunit_file
   *   A filepath to use for PHPUnit's --log-junit option.
   * @param int $status
   *   (optional) The exit status code of the PHPUnit process will be assigned
   *   to this variable.
   * @param string[] $output
   *   (optional) The output by running the phpunit command. If provided, this
   *   array will contain the lines output by the command.
   *
   * @return string
   *   The results as returned by exec().
   *
   * @internal
   */
  public function runCommand(array $unescaped_test_classnames, $phpunit_file, &$status = NULL, &$output = NULL) {
    global $base_url;
    // Setup an environment variable containing the database connection so that
    // functional tests can connect to the database.
    putenv('SIMPLETEST_DB=' . Database::getConnectionInfoAsUrl());

    // Setup an environment variable containing the base URL, if it is available.
    // This allows functional tests to browse the site under test. When running
    // tests via CLI, core/phpunit.xml.dist or core/scripts/run-tests.sh can set
    // this variable.
    if ($base_url) {
      putenv('SIMPLETEST_BASE_URL=' . $base_url);
      putenv('BROWSERTEST_OUTPUT_DIRECTORY=' . $this->workingDirectory);
    }
    $phpunit_bin = $this->phpUnitCommand();

    $command = [
      $phpunit_bin,
      '--log-junit',
      escapeshellarg($phpunit_file),
      '--printer',
      escapeshellarg(SimpletestUiPrinter::class),
    ];

    // Optimized for running a single test.
    if (count($unescaped_test_classnames) == 1) {
      $class = new \ReflectionClass($unescaped_test_classnames[0]);
      $command[] = escapeshellarg($class->getFileName());
    }
    else {
      // Double escape namespaces so they'll work in a regexp.
      $escaped_test_classnames = array_map(function ($class) {
        return addslashes($class);
      }, $unescaped_test_classnames);

      $filter_string = implode("|", $escaped_test_classnames);
      $command = array_merge($command, [
        '--filter',
        escapeshellarg($filter_string),
      ]);
    }

    // Need to change directories before running the command so that we can use
    // relative paths in the configuration file's exclusions.
    $old_cwd = getcwd();
    chdir($this->appRoot . "/core");

    // exec in a subshell so that the environment is isolated when running tests
    // via the simpletest UI.
    $ret = exec(implode(" ", $command), $output, $status);

    chdir($old_cwd);
    putenv('SIMPLETEST_DB=');
    if ($base_url) {
      putenv('SIMPLETEST_BASE_URL=');
      putenv('BROWSERTEST_OUTPUT_DIRECTORY=');
    }
    return $ret;
  }

  /**
   * Executes PHPUnit tests and returns the results of the run.
   *
   * @param int $test_id
   *   The current test ID.
   * @param string[] $unescaped_test_classnames
   *   An array of test class names, including full namespaces, to be passed as
   *   a regular expression to PHPUnit's --filter option.
   * @param int $status
   *   (optional) The exit status code of the PHPUnit process will be assigned
   *   to this variable.
   *
   * @return array
   *   The parsed results of PHPUnit's JUnit XML output, in the format of
   *   {simpletest}'s schema.
   *
   * @internal
   */
  public function runTests($test_id, array $unescaped_test_classnames, &$status = NULL) {
    $phpunit_file = $this->xmlLogFilePath($test_id);
    // Store output from our test run.
    $output = [];
    $this->runCommand($unescaped_test_classnames, $phpunit_file, $status, $output);

    if ($status == TestStatus::PASS) {
      return JUnitConverter::xmlToRows($test_id, $phpunit_file);
    }
    return [
      [
        'test_id' => $test_id,
        'test_class' => implode(",", $unescaped_test_classnames),
        'status' => TestStatus::label($status),
        'message' => 'PHPUnit Test failed to complete; Error: ' . implode("\n", $output),
        'message_group' => 'Other',
        'function' => implode(",", $unescaped_test_classnames),
        'line' => '0',
        'file' => $phpunit_file,
      ],
    ];
  }

  /**
   * Tallies test results per test class.
   *
   * @param string[][] $results
   *   Array of results in the {simpletest} schema. Can be the return value of
   *   PhpUnitTestRunner::runTests().
   *
   * @return int[][]
   *   Array of status tallies, keyed by test class name and status type.
   *
   * @internal
   */
  public function summarizeResults(array $results) {
    $summaries = [];
    foreach ($results as $result) {
      if (!isset($summaries[$result['test_class']])) {
        $summaries[$result['test_class']] = [
          '#pass' => 0,
          '#fail' => 0,
          '#exception' => 0,
          '#debug' => 0,
        ];
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
    return $summaries;
  }

}
