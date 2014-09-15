<?php

/**
 * @file
 * Contains Drupal\system\Tests\Entity\EntityDefinitionUpdateTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Entity\EntityStorageException;

/**
 * Tests EntityDefinitionUpdateManager functionality.
 *
 * @group Entity
 */
class EntityDefinitionUpdateTest extends EntityUnitTestBase {

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->database = $this->container->get('database');
  }

  /**
   * Tests when no definition update is needed.
   */
  public function testNoUpdates() {
    // Install every entity type's schema.
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }

    // Ensure that the definition update manager reports no updates.
    $this->assertFalse($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that no updates are needed.');
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), array(), 'EntityDefinitionUpdateManager reports an empty change summary.');

    // Ensure that applyUpdates() runs without error (it's not expected to do
    // anything when there aren't updates).
    $this->entityDefinitionUpdateManager->applyUpdates();
  }

  /**
   * Tests updating entity schema when there are no existing entities.
   */
  public function testUpdateWithoutData() {
    // Install every entity type's schema. Start with entity_test_rev not
    // supporting revisions, and ensure its revision table isn't created.
    $this->state->set('entity_test.entity_test_rev.disable_revisable', TRUE);
    $this->entityManager->clearCachedDefinitions();
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }
    $this->assertFalse($this->database->schema()->tableExists('entity_test_rev_revision'), 'Revision table not created for entity_test_rev.');

    // Restore entity_test_rev back to supporting revisions and ensure the
    // definition update manager reports that an update is needed.
    $this->state->delete('entity_test.entity_test_rev.disable_revisable');
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_rev' => array(
        t('Update the %entity_type entity type.', array('%entity_type' => $this->entityManager->getDefinition('entity_test_rev')->getLabel())),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the revision table is created.
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_rev_revision'), 'Revision table created for entity_test_rev.');
  }

  /**
   * Tests updating entity schema when there are existing entities.
   */
  public function testUpdateWithData() {
    // Install every entity type's schema. Start with entity_test_rev not
    // supporting revisions.
    $this->state->set('entity_test.entity_test_rev.disable_revisable', TRUE);
    $this->entityManager->clearCachedDefinitions();
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }

    // Save an entity.
    $this->entityManager->getStorage('entity_test_rev')->create()->save();

    // Restore entity_test_rev back to supporting revisions and try to apply
    // the update. It's expected to throw an exception.
    $this->state->delete('entity_test.entity_test_rev.disable_revisable');
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
    catch (EntityStorageException $e) {
      $this->pass('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
  }

}
