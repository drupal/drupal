<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\dependencies\MigrateDependenciesTest.
 */

namespace Drupal\migrate_drupal\Tests\dependencies;

use Drupal\Component\Utility\String;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Ensure the consistency among the dependencies for migrate.
 *
 * @group migrate_drupal
 * @group Drupal
 * @group migrate_drupal
 */
class MigrateDependenciesTest extends MigrateDrupalTestBase {

  static $modules = array('aggregator');

  /**
   * Tests that the order is correct when loading several migrations.
   */
  public function testMigrateDependenciesOrder() {
    $migration_items = array('d6_comment', 'd6_filter_format', 'd6_node');
    $migrations = entity_load_multiple('migration', $migration_items);
    $expected_order = array('d6_filter_format', 'd6_node', 'd6_comment');
    $this->assertEqual(array_keys($migrations), $expected_order);
    $expected_requirements = array(
      'd6_node',
      'd6_node_type',
      'd6_node_settings',
      'd6_field_instance_widget_settings',
      'd6_field_formatter_settings',
      'd6_filter_format',
      'd6_user',
      'd6_comment_type',
      'd6_comment_entity_display',
      'd6_comment_entity_form_display',
    );
    // Migration dependencies for comment include dependencies for node
    // migration as well.
    $actual_requirements = $migrations['d6_comment']->get('requirements');
    $this->assertEqual(count($actual_requirements), count($expected_requirements));
    foreach ($expected_requirements as $requirement) {
      $this->assertEqual($actual_requirements[$requirement], $requirement);
    }
  }

  /**
   * Tests dependencies on the migration of aggregator feeds & items.
   */
  public function testAggregatorMigrateDependencies() {
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_aggregator_item');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6AggregatorItem.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $this->startCollectingMessages();
    $executable->import();
    $this->assertEqual($this->migrateMessages['error'], array(String::format('Migration @id did not meet the requirements. Missing migrations d6_aggregator_feed. requirements: d6_aggregator_feed.', array('@id' => $migration->id()))));
    $this->collectMessages = FALSE;
  }

}
