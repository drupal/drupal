<?php

namespace Drupal\Tests\statistics\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of node counter data to Drupal 8.
 *
 * @group statistics
 */
class MigrateNodeCounterTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    'node',
    'statistics',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('statistics', ['node_counter']);

    $this->executeMigrations([
      'language',
      'd6_filter_format',
      'd6_user_role',
      'd6_node_settings',
      'd6_user',
      'd6_node_type',
      'd6_language_content_settings',
      'd6_node',
      'd6_node_translation',
      'statistics_node_counter',
    ]);
  }

  /**
   * Tests migration of node counter.
   */
  public function testStatisticsSettings() {
    $this->assertNodeCounter(1, 2, 0, 1421727536);
    $this->assertNodeCounter(2, 1, 0, 1471428059);
    $this->assertNodeCounter(3, 1, 0, 1471428153);
    $this->assertNodeCounter(4, 1, 1, 1478755275);
    $this->assertNodeCounter(5, 1, 1, 1478755314);
    $this->assertNodeCounter(10, 5, 1, 1521137459);
    $this->assertNodeCounter(12, 3, 0, 1521137469);

    // Tests that translated node counts include all translation counts.
    $this->executeMigration('statistics_node_translation_counter');
    $this->assertNodeCounter(10, 8, 2, 1521137463);
    $this->assertNodeCounter(12, 5, 1, 1521137470);
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
    $this->assertSame($total_count, $statistics->getTotalCount());
    $this->assertSame($day_count, $statistics->getDayCount());
    $this->assertSame($timestamp, $statistics->getTimestamp());
  }

}
