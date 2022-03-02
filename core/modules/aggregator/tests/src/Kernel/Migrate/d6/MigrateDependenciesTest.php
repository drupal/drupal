<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\migrate\MigrateExecutable;

/**
 * Ensure the consistency among the dependencies for migrate.
 *
 * @group aggregator
 */
class MigrateDependenciesTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['aggregator'];

  /**
   * Tests dependencies on the migration of aggregator feeds & items.
   */
  public function testAggregatorMigrateDependencies() {
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d6_aggregator_item');
    $executable = new MigrateExecutable($migration, $this);
    $this->startCollectingMessages();
    $executable->import();
    $this->assertEquals([new FormattableMarkup('Migration @id did not meet the requirements. Missing migrations d6_aggregator_feed. requirements: d6_aggregator_feed.', ['@id' => $migration->id()])], $this->migrateMessages['error']);
  }

}
