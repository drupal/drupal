<?php

declare(strict_types=1);

namespace Drupal\Core\Test;

use Drupal\Core\Test\Exception\MissingGroupException;
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
 * @phpstan-type TestClassInfo array{name: class-string, description: string, group: string|int, groups: list<string|int>, type: string, file: string, tests_count: positive-int}
 * @phpstan-type TestClassInfoList array<class-string,TestClassInfo>
 * @phpstan-type GroupedTestClassInfoList array<string|int,TestClassInfoList>
 *
 * @internal
 *
 * @final
 */
class PhpUnitTestDiscovery {

  /**
   * The singleton.
   *
   * @var \Drupal\Core\Test\PhpUnitTestDiscovery|null
   */
  private static ?self $instance = NULL;

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
   * @param list<string> $testGroups
   *   (optional) An array of test groups to filter the discovery for.
   *
   * @return GroupedTestClassInfoList
   *   An array of test groups keyed by the group name. Each test group is an
   *   array of test class information arrays as returned by
   *   ::getTestClassInfo(), keyed by test class. If a test class belongs to
   *   multiple groups, it will appear under all group keys it belongs to.
   */
  public function getTestClasses(?string $extension = NULL, array $testSuites = [], ?string $directory = NULL, array $testGroups = []): array {
    $this->warnings = [];

    $args = ['--configuration', $this->configurationFilePath];

    if (!empty($testSuites)) {
      foreach ($testSuites as $testSuite) {
        if (!is_string($testSuite)) {
          throw new \InvalidArgumentException("Test suite must be a string");
        }
        if (str_contains($testSuite, ' ')) {
          throw new \InvalidArgumentException("Test suite name '{$testSuite}' is invalid");
        }
      }
      $args[] = '--testsuite=' . implode(',', $testSuites);
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
      $this->getTestList($phpUnitTestSuite, $extension, $testGroups) :
      $this->getTestListLimitedToDirectory($phpUnitTestSuite, $extension, $testSuites, $testGroups);

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
   * @param list<string> $testGroups
   *   (optional) An array of test groups to filter the discovery for.
   *
   * @return array
   *   A classmap containing all discovered class files; i.e., a map of
   *   fully-qualified classnames to path names.
   */
  public function findAllClassFiles(?string $extension = NULL, ?string $directory = NULL, array $testGroups = []): array {
    $testClasses = $this->getTestClasses($extension, [], $directory, $testGroups);
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
   * @param list<string> $testGroups
   *   (optional) An array of test groups to filter the discovery for.
   *
   * @return GroupedTestClassInfoList
   *   An array of test groups keyed by the group name. Each test group is an
   *   array of test class information arrays as returned by
   *   ::getTestClassInfo(), keyed by test class. If a test class belongs to
   *   multiple groups, it will appear under all group keys it belongs to.
   */
  private function getTestList(TestSuite $phpUnitTestSuite, ?string $extension, array $testGroups): array {
    $list = [];
    foreach ($phpUnitTestSuite->tests() as $testSuite) {
      foreach ($testSuite->tests() as $testClass) {
        if ($testClass->isEmpty()) {
          continue;
        }

        if ($extension !== NULL && !str_starts_with($testClass->name(), "Drupal\\Tests\\{$extension}\\")) {
          continue;
        }

        $item = $this->getTestClassInfo($testClass, $testSuite->name());

        // Skip tests whose groups are not in the required ones.
        if (!empty($testGroups) && empty(array_intersect($item['groups'], $testGroups))) {
          continue;
        }

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
   * @param list<string> $testGroups
   *   (optional) An array of test groups to filter the discovery for.
   *
   * @return GroupedTestClassInfoList
   *   An array of test groups keyed by the group name. Each test group is an
   *   array of test class information arrays as returned by
   *   ::getTestClassInfo(), keyed by test class. If a test class belongs to
   *   multiple groups, it will appear under all group keys it belongs to.
   */
  private function getTestListLimitedToDirectory(TestSuite $phpUnitTestSuite, ?string $extension, array $testSuites, array $testGroups): array {
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
      $testSuite = self::getPhpunitTestSuite($phpUnitTestSuite->name());
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
      $testSuite = self::getPhpunitTestSuite($testClass->name());
      if (!empty($testSuites) && !in_array($testSuite, $testSuites, TRUE)) {
        continue;
      }

      $item = $this->getTestClassInfo($testClass, $testSuite);

      // Skip tests whose groups are not in the required ones.
      if (!empty($testGroups) && empty(array_intersect($item['groups'], $testGroups))) {
        continue;
      }

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
   * phpcs:ignore Drupal.Commenting.DataTypeNamespace.DataTypeNamespace
   * @return TestClassInfo
   *   The test class information.
   */
  private function getTestClassInfo(Test $testClass, string $testSuite): array {
    $reflection = new \ReflectionClass($testClass->name());

    // In PHPUnit 11+, we need to coalesce the groups from individual tests
    // as they may not be available from the test class level (when tests are
    // backed by data providers).
    $tmp = [];
    foreach ($testClass as $test) {
      if ($test instanceof DataProviderTestSuite) {
        foreach ($test as $testWithData) {
          if ($testWithData->valueObjectForEvents()->metadata()->isGroup()->isEmpty()) {
            throw new MissingGroupException(sprintf('Missing group metadata in test %s', $testWithData->valueObjectForEvents()->id()));
          }
          $tmp = array_merge($tmp, $testWithData->groups());
        }
      }
      else {
        if ($test->valueObjectForEvents()->metadata()->isGroup()->isEmpty()) {
          throw new MissingGroupException(sprintf('Missing group metadata in test %s', $test->valueObjectForEvents()->id()));
        }
        $tmp = array_merge($tmp, $test->groups());
      }
    }
    $groups = array_filter(array_unique($tmp), function (string $value): bool {
      return !str_starts_with($value, '__phpunit');
    });

    // Let PHPUnit API return the class coverage information.
    $test = $testClass;
    while (!$test instanceof TestCase) {
      $test = $test->tests()[0];
    }
    if (($metadata = $test->valueObjectForEvents()->metadata()->isCoversClass()) && $metadata->isNotEmpty()) {
      $description = sprintf('Tests %s.', $metadata->asArray()[0]->className());
    }
    else {
      $description = self::parseTestClassSummary($reflection->getDocComment() ?: '');
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

  /**
   * Parses the phpDoc summary line of a test class.
   *
   * @param string $doc_comment
   *   The documentation comment.
   *
   * @return string
   *   The parsed phpDoc summary line. An empty string is returned if no summary
   *   line can be parsed.
   */
  private static function parseTestClassSummary(string $doc_comment): string {
    // Normalize line endings.
    $doc_comment = preg_replace('/\r\n|\r/', '\n', $doc_comment);
    // Strip leading and trailing doc block lines.
    $doc_comment = substr($doc_comment, 4, -4);

    $lines = explode("\n", $doc_comment);
    $summary = [];
    // Add every line to the summary until the first empty line or annotation
    // is found.
    foreach ($lines as $line) {
      if (preg_match('/^[ ]*\*$/', $line) || preg_match('/^[ ]*\* \@/', $line)) {
        break;
      }
      $summary[] = trim($line, ' *');
    }
    return implode(' ', $summary);
  }

  /**
   * Determines the phpunit testsuite for a given classname, based on namespace.
   *
   * @param string $classname
   *   The test classname.
   *
   * @return string|false
   *   The testsuite name or FALSE if its not a phpunit test.
   */
  public static function getPhpunitTestSuite(string $classname): string|false {
    if (preg_match('/Drupal\\\\Tests\\\\(\w+)\\\\(\w+)/', $classname, $matches)) {
      if ($matches[1] === 'Component') {
        return 'unit-component';
      }
      // This could be an extension test, in which case the first match will be
      // the extension name. We assume that lower-case strings are module names.
      if (strtolower($matches[1]) == $matches[1]) {
        return match ($matches[2]) {
          'FunctionalJavascript' => 'functional-javascript',
          default => strtolower($matches[2]),
        };
      }
      return 'unit';
    }
    // Core tests.
    elseif (preg_match('/Drupal\\\\(\w*)Tests\\\\/', $classname, $matches)) {
      return match ($matches[1]) {
        '' => 'unit',
        'FunctionalJavascript' => 'functional-javascript',
        default => strtolower($matches[1]),
      };
    }
    return FALSE;
  }

}
