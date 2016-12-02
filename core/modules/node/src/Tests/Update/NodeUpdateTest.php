<?php

namespace Drupal\node\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that node settings are properly updated during database updates.
 *
 * @group node
 */
class NodeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that the node entity type has a 'published' entity key.
   *
   * @see node_update_8301()
   */
  public function testPublishedEntityKey() {
    // Check that the 'published' entity key does not exist prior to the update.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('node');
    $this->assertFalse($entity_type->getKey('published'));

    // Run updates.
    $this->runUpdates();

    // Check that the entity key exists and it has the correct value.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('node');
    $this->assertEqual('status', $entity_type->getKey('published'));
  }

}
