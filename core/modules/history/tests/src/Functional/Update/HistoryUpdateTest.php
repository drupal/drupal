<?php

namespace Drupal\Tests\history\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update functions for the History module.
 *
 * @group Update
 * @group legacy
 */
class HistoryUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['history'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests changing nid to entity_id and adding an entity_type field to the history table.
   *
   * @see history_update_8801()
   */
  public function testUpdateHookN() {
    $database = \Drupal::database();
    $schema = $database->schema();

    // Run updates.
    $this->runUpdates();

    // Ensure fields were added.
    $this->assertTrue($schema->fieldExists('history', 'entity_type'));
    $this->assertTrue($schema->fieldExists('history', 'entity_id'));
    // Ensure field was removed.
    $this->assertFalse($schema->fieldExists('history', 'nid'));

    $this->assertTrue($schema->indexExists('history', 'history_entity'));
    $this->assertFalse($schema->indexExists('history', 'nid'));

    $entries = $database->select('history')
      ->fields('history')
      ->condition('entity_type', 'node', '!=')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, $entries);
  }

}
