<?php

declare(strict_types=1);

namespace Drupal\TestTools\TestRunner;

/**
 * Allocates available tests to test workers.
 *
 * @phpstan-import-type TestClassInfo from \Drupal\Core\Test\PhpUnitTestDiscovery
 * @phpstan-import-type TestClassInfoList from \Drupal\Core\Test\PhpUnitTestDiscovery
 * @phpstan-import-type GroupedTestClassInfoList from \Drupal\Core\Test\PhpUnitTestDiscovery
 *
 * @internal
 */
class WorkAllocator {

  /**
   * The weight multiplier for tests in the '#slow' group.
   *
   * A test class weighs its number of test cases. Tests in the '#slow' group
   * take several times longer per test case than other tests, so their weight
   * is multiplied by this value.
   */
  const SLOW_TEST_WEIGHT = 8;

  /**
   * The sorted tests list, prior to allocation.
   *
   * @var TestClassInfoList
   */
  protected array $sortedList = [];

  /**
   * The tests list, allocated to current bin.
   *
   * @var TestClassInfoList
   */
  protected array $allocatedList = [];

  /**
   * @param GroupedTestClassInfoList $groupedTestClassInfoList
   *   The tests to allocate to workers.
   * @param int $totalBins
   *   The number of bins available for allocation.
   * @param int $binIndex
   *   The current bin.
   */
  public function __construct(
    public readonly array $groupedTestClassInfoList,
    public readonly int $totalBins = 1,
    public readonly int $binIndex = 1,
  ) {
    $this->process();
  }

  /**
   * Returns the sorted tests list, prior to allocation.
   *
   * @return TestClassInfoList
   *   The allocated tests.
   */
  public function getSortedList(): array {
    return $this->sortedList;
  }

  /**
   * Returns the list of tests allocated to current bin.
   *
   * @return TestClassInfoList
   *   The allocated tests.
   */
  public function getAllocatedList(): array {
    return $this->allocatedList;
  }

  /**
   * Allocates tests to workers.
   */
  private function process(): void {
    // Merge the tests from all groups into a unique list, since tests may
    // appear in more than one group, and weigh them.
    $tests = array_merge(...array_values($this->groupedTestClassInfoList));
    $slowTests = $this->groupedTestClassInfoList['#slow'] ?? [];
    foreach ($tests as $class => &$test) {
      $test['weight'] = $test['tests_count'] * (isset($slowTests[$class]) ? self::SLOW_TEST_WEIGHT : 1);
    }
    unset($test);

    // Sort all tests.
    $this->sortTestsByTypeAndWeight($tests);
    $this->sortedList = $tests;
    $this->assignTestsSequence($this->sortedList, 'sorted_sequence');

    // If the tests are not being run in parallel, the sorted list is enough.
    if ($this->totalBins <= 1) {
      $this->allocatedList = $this->sortedList;
      $this->assignTestsSequence($this->allocatedList, 'worker_sequence');
      return;
    }

    $binnedTests = $this->placeTestsIntoBins($this->sortedList);
    $this->allocatedList = $binnedTests[$this->binIndex - 1];
    $this->assignTestsSequence($this->allocatedList, 'worker_sequence');
  }

  /**
   * Sort tests by test type and weight.
   *
   * Tests with several test cases take longer to run than tests with a single
   * test case, all else being equal, so this sorts tests approximately from
   * the slowest to the fastest. Tests that are exceptionally slow can be added
   * to the '#slow' group to increase their weight.
   *
   * Every worker computes the same sort, so ties are broken by class name.
   *
   * @param TestClassInfoList $tests
   *   The tests to sort.
   */
  private function sortTestsByTypeAndWeight(array &$tests): void {
    uasort(
      $tests,
      fn(array $a, array $b): int => $this->getTestTypeWeight($b) <=> $this->getTestTypeWeight($a)
        ?: $b['weight'] <=> $a['weight']
          ?: strcmp($a['name'], $b['name']),
    );
  }

  /**
   * Weights a test class based on which test suite it belongs to.
   *
   * @param TestClassInfo $classInfo
   *   The test class info array.
   */
  private function getTestTypeWeight(array $classInfo): int {
    return match ($classInfo['type']) {
      'functional-javascript' => 3,
      'functional' => 2,
      'build' => 2,
      'kernel' => 1,
      default => 0,
    };
  }

  /**
   * Assigns the test sequence.
   *
   * @param TestClassInfoList $tests
   *   The array of test class info.
   * @param string $sequenceKey
   *   The key of the TestClassInfo to add.
   */
  private function assignTestsSequence(array &$tests, string $sequenceKey): void {
    $i = 0;
    foreach ($tests as &$testInfo) {
      $testInfo[$sequenceKey] = ++$i;
    }
  }

  /**
   * Distribute tests into bins of roughly equal total weight.
   *
   * Each test, in sorted order, goes into the bin with the lowest total weight
   * so far. When several bins have the lowest weight, the first one is used.
   *
   * @param TestClassInfoList $tests
   *   The sorted tests.
   *
   * @return array<int,TestClassInfoList>
   *   An associative array of bins and the test class names in each bin.
   */
  private function placeTestsIntoBins(array $tests): array {
    $bins = array_fill(0, $this->totalBins, []);
    $binWeights = array_fill(0, $this->totalBins, 0);
    foreach ($tests as $key => $test) {
      $bin = array_search(min($binWeights), $binWeights, TRUE);
      $bins[$bin][$key] = $test;
      $binWeights[$bin] += $test['weight'];
    }
    return $bins;
  }

}
