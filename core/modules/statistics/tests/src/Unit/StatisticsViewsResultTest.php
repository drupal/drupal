<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Unit;

use Drupal\statistics\StatisticsViewsResult;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\statistics\StatisticsViewsResult
 * @group statistics
 */
class StatisticsViewsResultTest extends UnitTestCase {

  /**
   * Tests migration of node counter.
   *
   * @covers ::__construct
   *
   * @dataProvider providerTestStatisticsCount
   */
  public function testStatisticsCount($total_count, $day_count, $timestamp) {
    $statistics = new StatisticsViewsResult($total_count, $day_count, $timestamp);
    $this->assertSame((int) $total_count, $statistics->getTotalCount());
    $this->assertSame((int) $day_count, $statistics->getDayCount());
    $this->assertSame((int) $timestamp, $statistics->getTimestamp());
  }

  public function providerTestStatisticsCount() {
    return [
      [2, 0, 1421727536],
      [1, 0, 1471428059],
      [1, 1, 1478755275],
      ['1', '1', '1478755275'],
    ];
  }

}
