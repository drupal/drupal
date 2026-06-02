<?php

declare(strict_types=1);

namespace Drupal\TestTools\TestRunner;

use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\BrowserTestBase;

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

    // Separate tests in the '#slow' group from the rest.
    $slowTests = $this->groupedTestClassInfoList['#slow'] ?? [];
    $notSlowTests = [];
    foreach ($this->groupedTestClassInfoList as $group => $tests) {
      if ($group === '#slow') {
        continue;
      }
      $notSlowTests = array_merge($notSlowTests, $tests);
    }

    // Filter slow tests out of the not slow tests and ensure a unique list
    // since tests may appear in more than one group.
    $notSlowTests = array_diff_key($notSlowTests, $slowTests);

    // Sort all tests.
    $this->sortTestsByTypeAndCount($slowTests);
    $this->sortTestsByTypeAndCount($notSlowTests);
    $this->sortedList = array_merge($slowTests, $notSlowTests);
    $this->assignTestsSequence($this->sortedList, 'sorted_sequence');

    // If the tests are not being run in parallel, the sorted list is enough.
    if ($this->totalBins <= 1) {
      $this->allocatedList = $this->sortedList;
      $this->assignTestsSequence($this->allocatedList, 'worker_sequence');
      return;
    }

    // Set up a bin per test runner. Loop over the slow tests and add them to
    // a bin one by one, this distributes the tests evenly across the bins.
    $binnedSlowTests = $this->placeTestsIntoBins($slowTests);
    $slowTestsForJob = $binnedSlowTests[$this->binIndex - 1];

    // And the same for the rest of the tests.
    $binnedOtherTests = $this->placeTestsIntoBins($notSlowTests);
    $otherTestsForJob = $binnedOtherTests[$this->binIndex - 1];

    $this->allocatedList = array_merge($slowTestsForJob, $otherTestsForJob);
    $this->assignTestsSequence($this->allocatedList, 'worker_sequence');
  }

  /**
   * Sort tests by test type and count of tests to be executed.
   *
   * Tests with several methods take longer to run than tests with a single
   * method all else being equal, so this allows tests runs to be sorted by
   * approximately the slowest to fastest tests. Tests that are exceptionally
   * slow can be added to the '#slow' group so they are placed first in each
   * test run regardless of the number of methods.
   *
   * @param TestClassInfoList $tests
   *   The tests to sort.
   */
  private function sortTestsByTypeAndCount(array &$tests): void {
    uasort(
      $tests,
      fn (array $a, array $b): int => $this->getTestTypeWeight($b['name']) <=> $this->getTestTypeWeight($a['name']) ?: $b['tests_count'] <=> $a['tests_count'],
    );
  }

  /**
   * Weights a test class based on which test base class it extends.
   *
   * @param string $class
   *   The test class name.
   */
  private function getTestTypeWeight(string $class): int {
    assert(class_exists($class), "$class does not exist");
    return match(TRUE) {
      is_subclass_of($class, WebDriverTestBase::class) => 3,
      is_subclass_of($class, BrowserTestBase::class) => 2,
      is_subclass_of($class, BuildTestBase::class) => 2,
      is_subclass_of($class, KernelTestBase::class) => 1,
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
   * Distribute tests into roughly equal sized bins.
   *
   * @param TestClassInfoList $tests
   *   An array of test class names.
   *
   * @return array<int,TestClassInfoList>
   *   An associative array of bins and the test class names in each bin.
   */
  private function placeTestsIntoBins(array $tests): array {
    $bins = array_fill(0, $this->totalBins, []);
    $i = 0;
    foreach ($tests as $key => $test) {
      $bins[($i++ % $this->totalBins)][$key] = $test;
    }
    return $bins;
  }

}
