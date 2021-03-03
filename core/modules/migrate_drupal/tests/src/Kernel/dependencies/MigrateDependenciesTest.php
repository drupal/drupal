<?php

namespace Drupal\Tests\migrate_drupal\Kernel\dependencies;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutable;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Ensure the consistency among the dependencies for migrate.
 *
 * @group migrate_drupal
 */
class MigrateDependenciesTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['aggregator', 'comment'];

  /**
   * Tests that the order is correct when loading several migrations.
   */
  public function testMigrationDependenciesOrder() {
    $migration_items = ['d6_comment', 'd6_filter_format', 'd6_node:page'];
    /** @var \Drupal\migrate\Plugin\RequirementsInterface[] $migrations */
    $migrations = $this->container->get('plugin.manager.migration')->createInstances($migration_items);
    $expected_order = ['d6_filter_format', 'd6_node:page', 'd6_comment'];
    $this->assertSame(array_keys($migrations), $expected_order);

    // Migration dependencies for comment include dependencies for node
    // migration as well. checkRequirements does not include migrations with
    // no rows in the exception, so node types with no content aren't included
    // in the list.
    try {
      $migrations['d6_comment']->checkRequirements();
      $this->fail("The requirements check failed to throw a RequirementsException");
    }
    catch (RequirementsException $e) {
      $this->assertEquals('Missing migrations d6_comment_type, d6_user, d6_comment_entity_display, d6_node_type, d6_comment_entity_form_display, d6_node_settings, d6_filter_format, d6_node:company, d6_node:employee, d6_node:forum, d6_node:page, d6_node:story, d6_node:test_planet.', $e->getMessage());
    }
    catch (\Exception $e) {
      $this->fail("The requirements check threw an exception, but it was not the expected RequirementsException");
    }
  }

  /**
   * Tests dependencies on the migration of aggregator feeds & items.
   */
  public function testAggregatorMigrateDependencies() {
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d6_aggregator_item');
    $executable = new MigrateExecutable($migration, $this);
    $this->startCollectingMessages();
    $executable->import();
    $this->assertEqual([new FormattableMarkup('Migration @id did not meet the requirements. Missing migrations d6_aggregator_feed. requirements: d6_aggregator_feed.', ['@id' => $migration->id()])], $this->migrateMessages['error']);
    $this->collectMessages = FALSE;
  }

}
