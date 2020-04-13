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

    $this->installSchema('node', ['node_access']);
    $this->installSchema('statistics', ['node_counter']);

    $this->migrateUsers(FALSE);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'language',
      'd7_language_content_settings',
      'd7_node',
      'd7_node_translation',
      'statistics_node_counter',
    ]);
  }

  /**
   * Tests migration of node counter.
   */
  public function testStatisticsSettings() {
    $this->assertNodeCounter(1, 2, 0, 1421727536);
    $this->assertNodeCounter(2, 1, 0, 1471428059);
    $this->assertNodeCounter(4, 1, 1, 1478755275);

    // Tests that translated node counts include all translation counts.
    $this->executeMigration('statistics_node_translation_counter');
    $this->assertNodeCounter(2, 2, 0, 1471428153);
    $this->assertNodeCounter(4, 2, 2, 1478755314);
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
