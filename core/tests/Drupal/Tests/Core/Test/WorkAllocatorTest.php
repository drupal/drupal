<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\TestRunner\WorkAllocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for WorkAllocator.
 */
#[CoversClass(WorkAllocator::class)]
#[Group('Test')]
class WorkAllocatorTest extends UnitTestCase {

  /**
   * Tests sorting and allocation of tests.
   */
  #[DataProvider('allocatorProvider')]
  public function testAllocator(int $totalBins, int $binIndex, array $groupedTestClassInfoList, array $expected): void {
    $allocator = new WorkAllocator($groupedTestClassInfoList, $totalBins, $binIndex);
    $this->assertEquals($expected, $allocator->getAllocatedList());
  }

  /**
   * Data for ::testAllocator.
   */
  public static function allocatorProvider(): \Generator {
    $path = __DIR__ . '/../../../../fixtures/test_runner/work_allocator';

    yield 'with slow test, single bin' => [
      1,
      1,
      json_decode(file_get_contents($path . '/simple_in.json'), TRUE),
      json_decode(file_get_contents($path . '/simple_out.json'), TRUE),
    ];

    yield 'with slow test, 2 bins, bin #1' => [
      2,
      1,
      json_decode(file_get_contents($path . '/simple_in.json'), TRUE),
      json_decode(file_get_contents($path . '/simple_one_of_two_out.json'), TRUE),
    ];

    // This is an edge case. Since we have 1 #slow test class and one other
    // test class not #slow, both classes get allocated to bin #1, and bin #2
    // remains empty, because the current algorithm allocates first all #slow
    // then all normal starting again. Does not happen in practice.
    yield 'with slow test, 2 bins, bin #2' => [
      2,
      2,
      json_decode(file_get_contents($path . '/simple_in.json'), TRUE),
      [],
    ];

    yield 'with slow test, 8 bins, bin #2' => [
      8,
      2,
      json_decode(file_get_contents($path . '/complex_in.json'), TRUE),
      json_decode(file_get_contents($path . '/complex_two_of_eight_out.json'), TRUE),
    ];

  }

}
