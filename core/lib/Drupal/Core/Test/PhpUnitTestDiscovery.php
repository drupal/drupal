<?php

declare(strict_types=1);

namespace Drupal\Core\Test;

use Drupal\Core\Test\Exception\MissingGroupException;
use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;
use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Framework\DataProviderTestSuite;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\Configuration\Builder;
use PHPUnit\TextUI\Configuration\TestSuiteBuilder;

/**
 * Discovers available tests using the PHPUnit API.
 *
 * @internal
 */
class PhpUnitTestDiscovery {

  /**
   * The singleton.
   *
   * @var \Drupal\Core\Test\PhpUnitTestDiscovery|null
   */
  private static ?self $instance = NULL;

  /**
   * The map of legacy test suite identifiers to phpunit.xml ones.
   *
   * @var array<string,string>
   */
  private array $map = [
    'PHPUnit-FunctionalJavascript' => 'functional-javascript',
    'PHPUnit-Functional' => 'functional',
    'PHPUnit-Kernel' => 'kernel',
    'PHPUnit-Unit' => 'unit',
    'PHPUnit-Unit-Component' => 'unit-component',
    'PHPUnit-Build' => 'build',
  ];

  /**
   * The reverse map of legacy test suite identifiers to phpunit.xml ones.
   *
   * @var array<string,string>
   */
  private array $reverseMap;

  /**
   * Path to PHPUnit's configuration file.
   */
  private string $configurationFilePath;

  /**
   * The warnings generated during the discovery.
   *
   * @var list<string>
   */
  private array $warnings = [];

  private function __construct() {
    $this->reverseMap = array_flip($this->map);
    try {
      EventFacade::instance()->registerTracer(new PhpUnitTestDiscoveryTracer($this));
      EventFacade::instance()->seal();
    }
    catch (EventFacadeIsSealedException) {
      // Just continue.
    }
  }

  /**
   * Returns the singleton instance.
   */
  public static function instance(): self {
    if (self::$instance === NULL) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Sets the configuration file path.
   */
  public function setConfigurationFilePath(string $configurationFilePath): self {
    $this->configurationFilePath = $configurationFilePath;
    return $this;
  }

  /**
   * Discovers available tests.
   *
   * @param string|null $extension
   *   (optional) The name of an extension to limit discovery to; e.g., 'node'.
   * @param list<string> $testSuites
   *   (optional) An array of PHPUnit test suites to filter the discovery for.
   * @param string|null $directory
   *   (optional) Limit discovered tests to a specific directory.
   *
   * @return array<string<array<class-string, array{name: class-string, description: string, group: string|int, groups: list<string|int>, type: string, file: string, tests_count: positive-int}>>>
   *   An array of test groups keyed by the group name. Each test group is an
   *   array of test class information arrays as returned by
   *   ::getTestClassInfo(), keyed by test class. If a test class belongs to
   *   multiple groups, it will appear under all group keys it belongs to.
   */
  public function getTestClasses(?string $extension = NULL, array $testSuites = [], ?string $directory = NULL): array {
    $this->warnings = [];

    $args = ['--configuration', $this->configurationFilePath];

    if (!empty($testSuites)) {
      // Convert $testSuites from Drupal's legacy syntax to the syntax used in
      // phpunit.xml, that is necessary to PHPUnit to be able to apply the
      // test suite filter. For example, 'PHPUnit-Unit' to 'unit'.
      $tmp = [];
      foreach ($testSuites as $i) {
        if (!is_string($i)) {
          throw new \InvalidArgumentException("Test suite must be a string");
        }
        if (str_contains($i, ' ')) {
          throw new \InvalidArgumentException("Test suite name '{$i}' is invalid");
        }
        $tmp[] = $this->map[$i] ?? $i;
      }
      $args[] = '--testsuite=' . implode(',', $tmp);
    }

    if ($directory !== NULL) {
      $args[] = $directory;
    }

    $phpUnitConfiguration = (new Builder())->build($args);

    // TestSuiteBuilder calls the test data providers during the discovery.
    // Data providers may be changing the Drupal service container, which leads
    // to potential issues. We save the current container before running the
    // discovery, and in case a change is detected, reset it and raise
    // warnings so that developers can tune their data provider code.
    if (\Drupal::hasContainer()) {
      $container = \Drupal::getContainer();
      $containerObjectId = spl_object_id($container);
    }
    $phpUnitTestSuite = (new TestSuiteBuilder())->build($phpUnitConfiguration);
    if (isset($containerObjectId) && $containerObjectId !== spl_object_id(\Drupal::getContainer())) {
      $this->addWarning(
        ">>> The service container was changed during the test discovery <<<\n" .
        "Probably, a test data provider method called \\Drupal::setContainer().\n" .
        "Ensure that all the data providers restore the original container before returning data."
      );
      assert(isset($container));
      \Drupal::setContainer($container);
    }

    $list = $directory === NULL ?
      $this->getTestList($phpUnitTestSuite, $extension) :
      $this->getTestListLimitedToDirectory($phpUnitTestSuite, $extension, $testSuites);

    // Sort the groups and tests within the groups by name.
    uksort($list, 'strnatcasecmp');
    foreach ($list as &$tests) {
      uksort($tests, 'strnatcasecmp');
    }

    return $list;
  }

  /**
   * Discovers all class files in all available extensions.
   *
   * @param string|null $extension
   *   (optional) The name of an extension to limit discovery to; e.g., 'node'.
   * @param string|null $directory
   *   (optional) Limit discovered tests to a specific directory.
   *
   * @return array
   *   A classmap containing all discovered class files; i.e., a map of
   *   fully-qualified classnames to path names.
   */
  public function findAllClassFiles(?string $extension = NULL, ?string $directory = NULL): array {
    $testClasses = $this->getTestClasses($extension, [], $directory);
    $classMap = [];
    foreach ($testClasses as $group) {
      foreach ($group as $className => $info) {
        $classMap[$className] = $info['file'];
      }
    }
    return $classMap;
  }

  /**
   * Adds warning message generated during the discovery.
   *
   * @param string $message
   *   The warning message.
   */
  public function addWarning(string $message): void {
    $this->warnings[] = $message;
  }

  /**
   * Returns the warnings generated during the discovery.
   *
   * @return list<string>
   *   The warnings.
   */
  public function getWarnings(): array {
    return $this->warnings;
  }

  /**
   * Returns a list of tests from a TestSuite object.
   *
   * @param \PHPUnit\Framework\TestSuite $phpUnitTestSuite
   *   The TestSuite object returned by PHPUnit test discovery.
   * @param string|null $extension
   *   The name of an extension to limit discovery to; e.g., 'node'.
   *
   * @return array<string<array<class-string, array{name: class-string, description: string, group: string|int, groups: list<string|int>, type: string, file: string, tests_count: positive-int}>>>
   *   An array of test groups keyed by the group name. Each test group is an
   *   array of test class information arrays as returned by
   *   ::getTestClassInfo(), keyed by test class. If a test class belongs to
   *   multiple groups, it will appear under all group keys it belongs to.
   */
  private function getTestList(TestSuite $phpUnitTestSuite, ?string $extension): array {
    $list = [];
    foreach ($phpUnitTestSuite->tests() as $testSuite) {
      foreach ($testSuite->tests() as $testClass) {
        if ($testClass->isEmpty()) {
          continue;
        }

        if ($extension !== NULL && !str_starts_with($testClass->name(), "Drupal\\Tests\\{$extension}\\")) {
          continue;
        }

        $item = $this->getTestClassInfo(
          $testClass,
          $this->reverseMap[$testSuite->name()] ?? $testSuite->name(),
        );

        foreach ($item['groups'] as $group) {
          $list[$group][$item['name']] = $item;
        }
      }
    }
    return $list;
  }

  /**
   * Returns a list of tests from a TestSuite object limited to a directory.
   *
   * @param \PHPUnit\Framework\TestSuite $phpUnitTestSuite
   *   The TestSuite object returned by PHPUnit test discovery.
   * @param string|null $extension
   *   The name of an extension to limit discovery to; e.g., 'node'.
   * @param list<string> $testSuites
   *   An array of PHPUnit test suites to filter the discovery for.
   *
   * @return array<string<array<class-string, array{name: class-string, description: string, group: string|int, groups: list<string|int>, type: string, file: string, tests_count: positive-int}>>>
   *   An array of test groups keyed by the group name. Each test group is an
   *   array of test class information arrays as returned by
   *   ::getTestClassInfo(), keyed by test class. If a test class belongs to
   *   multiple groups, it will appear under all group keys it belongs to.
   */
  private function getTestListLimitedToDirectory(TestSuite $phpUnitTestSuite, ?string $extension, array $testSuites): array {
    $list = [];

    // In this case, PHPUnit found a single test class to run tests for.
    if ($phpUnitTestSuite->isForTestClass()) {
      if ($phpUnitTestSuite->isEmpty()) {
        return [];
      }

      if ($extension !== NULL && !str_starts_with($phpUnitTestSuite->name(), "Drupal\\Tests\\{$extension}\\")) {
        return [];
      }

      // Take the test suite name from the class namespace.
      $testSuite = 'PHPUnit-' . TestDiscovery::getPhpunitTestSuite($phpUnitTestSuite->name());
      if (!empty($testSuites) && !in_array($testSuite, $testSuites, TRUE)) {
        return [];
      }

      $item = $this->getTestClassInfo($phpUnitTestSuite, $testSuite);

      foreach ($item['groups'] as $group) {
        $list[$group][$item['name']] = $item;
      }
      return $list;
    }

    // Multiple test classes were found.
    $list = [];
    foreach ($phpUnitTestSuite->tests() as $testClass) {
      if ($testClass->isEmpty()) {
        continue;
      }

      if ($extension !== NULL && !str_starts_with($testClass->name(), "Drupal\\Tests\\{$extension}\\")) {
        continue;
      }

      // Take the test suite name from the class namespace.
      $testSuite = 'PHPUnit-' . TestDiscovery::getPhpunitTestSuite($testClass->name());
      if (!empty($testSuites) && !in_array($testSuite, $testSuites, TRUE)) {
        continue;
      }

      $item = $this->getTestClassInfo($testClass, $testSuite);

      foreach ($item['groups'] as $group) {
        $list[$group][$item['name']] = $item;
      }
    }
    return $list;

  }

  /**
   * Returns the test class information.
   *
   * @param \PHPUnit\Framework\Test $testClass
   *   The test class.
   * @param string $testSuite
   *   The test suite of this test class.
   *
   * @return array{name: class-string, description: string, group: string|int, groups: list<string|int>, type: string, file: string, tests_count: positive-int}
   *   The test class information.
   */
  private function getTestClassInfo(Test $testClass, string $testSuite): array {
    $reflection = new \ReflectionClass($testClass->name());

    // Let PHPUnit API return the groups, as it will deal transparently with
    // annotations or attributes, but skip groups generated by PHPUnit
    // internally and starting with a double underscore prefix.
    if (RunnerVersion::getMajor() < 11) {
      $groups = array_filter($testClass->groups(), function (string $value): bool {
        return !str_starts_with($value, '__phpunit');
      });
    }
    else {
      // In PHPUnit 11+, we need to coalesce the groups from individual tests
      // as they may not be available from the test class level (when tests are
      // backed by data providers).
      $tmp = [];
      foreach ($testClass as $test) {
        if ($test instanceof DataProviderTestSuite) {
          foreach ($test as $testWithData) {
            $tmp = array_merge($tmp, $testWithData->groups());
          }
        }
        else {
          $tmp = array_merge($tmp, $test->groups());
        }
      }
      $groups = array_filter(array_unique($tmp), function (string $value): bool {
        return !str_starts_with($value, '__phpunit');
      });
    }
    if (empty($groups)) {
      throw new MissingGroupException(sprintf('Missing group metadata in test class %s', $testClass->name()));
    }

    // Let PHPUnit API return the class coverage information.
    $test = $testClass;
    while (!$test instanceof TestCase) {
      $test = $test->tests()[0];
    }
    if (($metadata = $test->valueObjectForEvents()->metadata()->isCoversClass()) && $metadata->isNotEmpty()) {
      $description = sprintf('Tests %s.', $metadata->asArray()[0]->className());
    }
    elseif (($metadata = $test->valueObjectForEvents()->metadata()->isCoversDefaultClass()) && $metadata->isNotEmpty()) {
      $description = sprintf('Tests %s.', $metadata->asArray()[0]->className());
    }
    else {
      $description = TestDiscovery::parseTestClassSummary($reflection->getDocComment());
    }

    // Find the test cases count.
    $count = 0;
    foreach ($testClass->tests() as $testCase) {
      if ($testCase instanceof TestCase) {
        // If it's a straight test method, counts 1.
        $count++;
      }
      else {
        // It's a data provider test suite, count 1 per data set provided.
        $count += count($testCase->tests());
      }
    }

    return [
      'name' => $testClass->name(),
      'group' => $groups[0],
      'groups' => $groups,
      'type' => $testSuite,
      'description' => $description,
      'file' => $reflection->getFileName(),
      'tests_count' => $count,
    ];
  }

}
