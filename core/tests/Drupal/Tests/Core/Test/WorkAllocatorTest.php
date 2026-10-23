<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\TestRunner\WorkAllocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for WorkAllocator.
 *
 * @phpstan-import-type TestClassInfo from \Drupal\Core\Test\PhpUnitTestDiscovery
 */
#[CoversClass(WorkAllocator::class)]
#[Group('Test')]
class WorkAllocatorTest extends UnitTestCase {

  /**
   * Tests sorting tests into a single bin.
   */
  public function testSingleBin(): void {
    $path = __DIR__ . '/../../../../fixtures/test_runner/work_allocator';
    $allocator = new WorkAllocator(json_decode(file_get_contents($path . '/simple_in.json'), TRUE));
    $this->assertEquals(json_decode(file_get_contents($path . '/simple_out.json'), TRUE), $allocator->getAllocatedList());
  }

  /**
   * Tests that each test goes into the bin with the lowest total weight.
   *
   * The test classes only need to be existing classes, as the allocator uses
   * their names but never loads them.
   */
  public function testLeastWeightedBin(): void {
    $a = \ArrayObject::class;
    $b = \DateTime::class;
    $c = \Exception::class;
    $d = \SplQueue::class;
    $e = \SplStack::class;
    $tests = [
      $a => static::testClassInfo($a, 3),
      $b => static::testClassInfo($b, 3),
      $c => static::testClassInfo($c, 2),
      $d => static::testClassInfo($d, 1),
      $e => static::testClassInfo($e, 1, TRUE),
    ];
    $grouped = ['group' => $tests, '#slow' => [$e => $tests[$e]]];

    // The #slow test weighs 1 × 8, so the order is e (8), a (3), b (3), c (2),
    // d (1), with ties broken by class name. e goes to bin 1, a to bin 2, b
    // to bin 2 (3 < 8), c to bin 2 (6 < 8), then d to bin 1 (8 = 8, the first
    // bin wins).
    $bin1 = new WorkAllocator($grouped, 2, 1);
    $this->assertSame([$e, $d], array_keys($bin1->getAllocatedList()));
    $this->assertSame([1, 2], array_column($bin1->getAllocatedList(), 'worker_sequence'));
    $bin2 = new WorkAllocator($grouped, 2, 2);
    $this->assertSame([$a, $b, $c], array_keys($bin2->getAllocatedList()));
    $this->assertSame([$e, $a, $b, $c, $d], array_keys($bin1->getSortedList()));
  }

  /**
   * Tests that every test is allocated to exactly one bin.
   */
  public function testEveryTestAllocatedOnce(): void {
    $grouped = json_decode(file_get_contents(__DIR__ . '/../../../../fixtures/test_runner/work_allocator/complex_in.json'), TRUE);
    $expected = array_keys(array_merge(...array_values($grouped)));

    $allocated = [];
    for ($bin = 1; $bin <= 8; $bin++) {
      $allocated = array_merge($allocated, array_keys((new WorkAllocator($grouped, 8, $bin))->getAllocatedList()));
    }
    sort($expected);
    sort($allocated);
    $this->assertSame($expected, $allocated);
  }

  /**
   * Builds test class information for a kernel test.
   *
   * @param class-string $class
   *   The test class.
   * @param positive-int $testsCount
   *   The number of test cases.
   * @param bool $slow
   *   Whether the test is in the '#slow' group.
   *
   * @return TestClassInfo
   *   The test class information.
   */
  protected static function testClassInfo(string $class, int $testsCount, bool $slow = FALSE): array {
    return [
      'name' => $class,
      'group' => 'group',
      'groups' => $slow ? ['group', '#slow'] : ['group'],
      'type' => 'kernel',
      'description' => '',
      'file' => '',
      'tests_count' => $testsCount,
    ];
  }

}
