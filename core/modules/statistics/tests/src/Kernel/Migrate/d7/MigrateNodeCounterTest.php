<?php

namespace Drupal\Tests\statistics\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of node counter data to Drupal 8.
 *
 * @group statistics
 */
class MigrateNodeCounterTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'menu_ui',
    'node',
    'statistics',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->installSchema('statistics', ['node_counter']);

    $this->executeMigrations([
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node',
      'statistics_node_counter'
    ]);
  }

  /**
   * Tests migration of node counter.
   */
  public function testStatisticsSettings() {
    $this->assertNodeCounter(1, 2, 0, 1421727536);
    $this->assertNodeCounter(2, 1, 0, 1471428059);
    $this->assertNodeCounter(4, 1, 1, 1478755275);
  }

  /**
   * Asserts various aspects of a node counter.
   *
   * @param int $nid
   *   The node ID.
   * @param int $total_count
   *   The expected total count.
   * @param int $day_count
   *   The expected day count.
   * @param int $timestamp
   *   The expected timestamp.
   */
  protected function assertNodeCounter($nid, $total_count, $day_count, $timestamp) {
    /** @var \Drupal\statistics\StatisticsViewsResult $statistics */
    $statistics = $this->container->get('statistics.storage.node')->fetchView($nid);
    // @todo Remove casting after https://www.drupal.org/node/2926069 lands.
    $this->assertSame($total_count, (int) $statistics->getTotalCount());
    $this->assertSame($day_count, (int) $statistics->getDayCount());
    $this->assertSame($timestamp, (int) $statistics->getTimestamp());
  }

}
