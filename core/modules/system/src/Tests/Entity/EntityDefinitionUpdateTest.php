<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityDefinitionUpdateTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionEvents;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\FieldStorageDefinition;

/**
 * Tests EntityDefinitionUpdateManager functionality.
 *
 * @group Entity
 */
class EntityDefinitionUpdateTest extends EntityUnitTestBase {

  use EntityDefinitionTestTrait;

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

    // Install every entity type's schema that wasn't installed in the parent
    // method.
    foreach (array_diff_key($this->entityManager->getDefinitions(), array_flip(array('user', 'entity_test'))) as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }
  }

  /**
   * Tests that new entity type definitions are correctly handled.
   */
  public function testNewEntityType() {
    $entity_type_id = 'entity_test_new';
    $schema = $this->database->schema();

    // Check that the "entity_test_new" is not defined.
    $entity_types = $this->entityManager->getDefinitions();
    $this->assertFalse(isset($entity_types[$entity_type_id]), 'The "entity_test_new" entity type does not exist.');
    $this->assertFalse($schema->tableExists($entity_type_id), 'Schema for the "entity_test_new" entity type does not exist.');

    // Check that the "entity_test_new" is now defined and the related schema
    // has been created.
    $this->enableNewEntityType();
    $entity_types = $this->entityManager->getDefinitions();
    $this->assertTrue(isset($entity_types[$entity_type_id]), 'The "entity_test_new" entity type exists.');
    $this->assertTrue($schema->tableExists($entity_type_id), 'Schema for the "entity_test_new" entity type has been created.');
  }

  /**
   * Tests when no definition update is needed.
   */
  public function testNoUpdates() {
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
  public function testEntityTypeUpdateWithoutData() {
    // The 'entity_test_update' entity type starts out non-revisionable, so
    // ensure the revision table hasn't been created during setUp().
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update_revision'), 'Revision table not created for entity_test_update.');

    // Update it to be revisionable and ensure the definition update manager
    // reports that an update is needed.
    $this->updateEntityTypeToRevisionable();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %entity_type entity type.', array('%entity_type' => $this->entityManager->getDefinition('entity_test_update')->getLabel())),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected); //, 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the revision table is created.
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update_revision'), 'Revision table created for entity_test_update.');
  }

  /**
   * Tests updating entity schema when there are existing entities.
   */
  public function testEntityTypeUpdateWithData() {
    // Save an entity.
    $this->entityManager->getStorage('entity_test_update')->create()->save();

    // Update the entity type to be revisionable and try to apply the update.
    // It's expected to throw an exception.
    $this->updateEntityTypeToRevisionable();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
    catch (EntityStorageException $e) {
      $this->pass('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
  }

  /**
   * Tests creating, updating, and deleting a base field if no entities exist.
   */
  public function testBaseFieldCreateUpdateDeleteWithoutData() {
    // Add a base field, ensure the update manager reports it, and the update
    // creates its schema.
    $this->addBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Create the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Column created in shared table for new_base_field.');

    // Add an index on the base field, ensure the update manager reports it,
    // and the update creates it.
    $this->addBaseFieldIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), 'Index created.');

    // Remove the above index, ensure the update manager reports it, and the
    // update deletes it.
    $this->removeBaseFieldIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), 'Index deleted.');

    // Update the type of the base field from 'string' to 'text', ensure the
    // update manager reports it, and the update adjusts the schema
    // accordingly.
    $this->modifyBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Original column deleted in shared table for new_base_field.');
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field__value'), 'Value column created in shared table for new_base_field.');
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field__format'), 'Format column created in shared table for new_base_field.');

    // Remove the base field, ensure the update manager reports it, and the
    // update deletes the schema.
    $this->removeBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Delete the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field_value'), 'Value column deleted from shared table for new_base_field.');
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field_format'), 'Format column deleted from shared table for new_base_field.');
  }

  /**
   * Tests creating, updating, and deleting a bundle field if no entities exist.
   */
  public function testBundleFieldCreateUpdateDeleteWithoutData() {
    // Add a bundle field, ensure the update manager reports it, and the update
    // creates its schema.
    $this->addBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Create the %field_name field.', array('%field_name' => t('A new bundle field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table created for new_bundle_field.');

    // Update the type of the base field from 'string' to 'text', ensure the
    // update manager reports it, and the update adjusts the schema
    // accordingly.
    $this->modifyBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new bundle field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update__new_bundle_field', 'new_bundle_field_format'), 'Format column created in dedicated table for new_base_field.');

    // Remove the bundle field, ensure the update manager reports it, and the
    // update deletes the schema.
    $this->removeBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Delete the %field_name field.', array('%field_name' => t('A new bundle field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table deleted for new_bundle_field.');
  }

  /**
   * Tests creating and deleting a base field if entities exist.
   *
   * This tests deletion when there are existing entities, but not existing data
   * for the field being deleted.
   *
   * @see testBaseFieldDeleteWithExistingData()
   */
  public function testBaseFieldCreateDeleteWithExistingEntities() {
    // Save an entity.
    $name = $this->randomString();
    $storage = $this->entityManager->getStorage('entity_test_update');
    $entity = $storage->create(array('name' => $name));
    $entity->save();

    // Add a base field and run the update. Ensure the base field's column is
    // created and the prior saved entity data is still there.
    $this->addBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $schema_handler = $this->database->schema();
    $this->assertTrue($schema_handler->fieldExists('entity_test_update', 'new_base_field'), 'Column created in shared table for new_base_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field creation.');

    // Remove the base field and run the update. Ensure the base field's column
    // is deleted and the prior saved entity data is still there.
    $this->removeBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($schema_handler->fieldExists('entity_test_update', 'new_base_field'), 'Column deleted from shared table for new_base_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field deletion.');

    // Add a base field with a required property and run the update. Ensure
    // 'not null' is not applied and thus no exception is thrown.
    $this->addBaseField('shape_required');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $assert = $schema_handler->fieldExists('entity_test_update', 'new_base_field__shape') && $schema_handler->fieldExists('entity_test_update', 'new_base_field__color');
    $this->assertTrue($assert, 'Columns created in shared table for new_base_field.');

    // Recreate the field after emptying the base table and check that its
    // columns are not 'not null'.
    // @todo Revisit this test when allowing for required storage field
    //   definitions. See https://www.drupal.org/node/2390495.
    $entity->delete();
    $this->removeBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $assert = !$schema_handler->fieldExists('entity_test_update', 'new_base_field__shape') && !$schema_handler->fieldExists('entity_test_update', 'new_base_field__color');
    $this->assert($assert, 'Columns removed from the shared table for new_base_field.');
    $this->addBaseField('shape_required');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $assert = $schema_handler->fieldExists('entity_test_update', 'new_base_field__shape') && $schema_handler->fieldExists('entity_test_update', 'new_base_field__color');
    $this->assertTrue($assert, 'Columns created again in shared table for new_base_field.');
    $entity = $storage->create(array('name' => $name));
    $entity->save();
    $this->pass('The new_base_field columns are still nullable');
  }

  /**
   * Tests creating and deleting a bundle field if entities exist.
   *
   * This tests deletion when there are existing entities, but not existing data
   * for the field being deleted.
   *
   * @see testBundleFieldDeleteWithExistingData()
   */
  public function testBundleFieldCreateDeleteWithExistingEntities() {
    // Save an entity.
    $name = $this->randomString();
    $storage = $this->entityManager->getStorage('entity_test_update');
    $entity = $storage->create(array('name' => $name));
    $entity->save();

    // Add a bundle field and run the update. Ensure the bundle field's table
    // is created and the prior saved entity data is still there.
    $this->addBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $schema_handler = $this->database->schema();
    $this->assertTrue($schema_handler->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table created for new_bundle_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field creation.');

    // Remove the base field and run the update. Ensure the bundle field's
    // table is deleted and the prior saved entity data is still there.
    $this->removeBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($schema_handler->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table deleted for new_bundle_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field deletion.');

    // Test that required columns are created as 'not null'.
    $this->addBundleField('shape_required');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $message = 'The new_bundle_field_shape column is not nullable.';
    $values = array(
      'bundle' => $entity->bundle(),
      'deleted'=> 0,
      'entity_id' => $entity->id(),
      'revision_id' => $entity->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'delta' => 0,
      'new_bundle_field_color' => $this->randomString(),
    );
    try {
      // Try to insert a record without providing a value for the 'not null'
      // column. This should fail.
      $this->database->insert('entity_test_update__new_bundle_field')
        ->fields($values)
        ->execute();
      $this->fail($message);
    }
    catch (\RuntimeException $e) {
      if ($e instanceof DatabaseExceptionWrapper || $e instanceof IntegrityConstraintViolationException) {
        // Now provide a value for the 'not null' column. This is expected to
        // succeed.
        $values['new_bundle_field_shape'] = $this->randomString();
        $this->database->insert('entity_test_update__new_bundle_field')
          ->fields($values)
          ->execute();
        $this->pass($message);
      } else {
        // Keep throwing it.
        throw $e;
      }
    }
  }

  /**
   * Tests deleting a base field when it has existing data.
   */
  public function testBaseFieldDeleteWithExistingData() {
    // Add the base field and run the update.
    $this->addBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the base field populated.
    $this->entityManager->getStorage('entity_test_update')->create(array('new_base_field' => 'foo'))->save();

    // Remove the base field and apply updates. It's expected to throw an
    // exception.
    // @todo Revisit that expectation once purging is implemented for
    //   all fields: https://www.drupal.org/node/2282119.
    $this->removeBaseField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
  }

  /**
   * Tests deleting a bundle field when it has existing data.
   */
  public function testBundleFieldDeleteWithExistingData() {
    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the bundle field populated.
    entity_test_create_bundle('custom');
    $this->entityManager->getStorage('entity_test_update')->create(array('type' => 'test_bundle', 'new_bundle_field' => 'foo'))->save();

    // Remove the bundle field and apply updates. It's expected to throw an
    // exception.
    // @todo Revisit that expectation once purging is implemented for
    //   all fields: https://www.drupal.org/node/2282119.
    $this->removeBundleField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
  }

  /**
   * Tests updating a base field when it has existing data.
   */
  public function testBaseFieldUpdateWithExistingData() {
    // Add the base field and run the update.
    $this->addBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the base field populated.
    $this->entityManager->getStorage('entity_test_update')->create(array('new_base_field' => 'foo'))->save();

    // Change the field's field type and apply updates. It's expected to
    // throw an exception.
    $this->modifyBaseField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
  }

  /**
   * Tests updating a bundle field when it has existing data.
   */
  public function testBundleFieldUpdateWithExistingData() {
    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the bundle field populated.
    entity_test_create_bundle('custom');
    $this->entityManager->getStorage('entity_test_update')->create(array('type' => 'test_bundle', 'new_bundle_field' => 'foo'))->save();

    // Change the field's field type and apply updates. It's expected to
    // throw an exception.
    $this->modifyBundleField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
  }

  /**
   * Tests creating and deleting a multi-field index when there are no existing entities.
   */
  public function testEntityIndexCreateDeleteWithoutData() {
    // Add an entity index and ensure the update manager reports that as an
    // update to the entity type.
    $this->addEntityIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %entity_type entity type.', array('%entity_type' => $this->entityManager->getDefinition('entity_test_update')->getLabel())),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the new index is created.
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index created.');

    // Remove the index and ensure the update manager reports that as an
    // update to the entity type.
    $this->removeEntityIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %entity_type entity type.', array('%entity_type' => $this->entityManager->getDefinition('entity_test_update')->getLabel())),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the index is deleted.
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index deleted.');
  }

  /**
   * Tests creating a multi-field index when there are existing entities.
   */
  public function testEntityIndexCreateWithData() {
    // Save an entity.
    $name = $this->randomString();
    $entity = $this->entityManager->getStorage('entity_test_update')->create(array('name' => $name));
    $entity->save();

    // Add an entity index, run the update. Ensure that the index is created
    // despite having data.
    $this->addEntityIndex();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index added.');
  }

  /**
   * Tests entity type and field storage definition events.
   */
  public function testDefinitionEvents() {
    /** @var \Drupal\entity_test\EntityTestDefinitionSubscriber $event_subscriber */
    $event_subscriber = $this->container->get('entity_test.definition.subscriber');
    $event_subscriber->enableEventTracking();

    // Test field storage definition events.
    $storage_definition = current($this->entityManager->getFieldStorageDefinitions('entity_test_rev'));
    $this->assertFalse($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::DELETE), 'Entity type delete was not dispatched yet.');
    $this->entityManager->onFieldStorageDefinitionDelete($storage_definition);
    $this->assertTrue($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::DELETE), 'Entity type delete event successfully dispatched.');
    $this->assertFalse($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::CREATE), 'Entity type create was not dispatched yet.');
    $this->entityManager->onFieldStorageDefinitionCreate($storage_definition);
    $this->assertTrue($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::CREATE), 'Entity type create event successfully dispatched.');
    $this->assertFalse($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::UPDATE), 'Entity type update was not dispatched yet.');
    $this->entityManager->onFieldStorageDefinitionUpdate($storage_definition, $storage_definition);
    $this->assertTrue($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::UPDATE), 'Entity type update event successfully dispatched.');

    // Test entity type events.
    $entity_type = $this->entityManager->getDefinition('entity_test_rev');
    $this->assertFalse($event_subscriber->hasEventFired(EntityTypeEvents::CREATE), 'Entity type create was not dispatched yet.');
    $this->entityManager->onEntityTypeCreate($entity_type);
    $this->assertTrue($event_subscriber->hasEventFired(EntityTypeEvents::CREATE), 'Entity type create event successfully dispatched.');
    $this->assertFalse($event_subscriber->hasEventFired(EntityTypeEvents::UPDATE), 'Entity type update was not dispatched yet.');
    $this->entityManager->onEntityTypeUpdate($entity_type, $entity_type);
    $this->assertTrue($event_subscriber->hasEventFired(EntityTypeEvents::UPDATE), 'Entity type update event successfully dispatched.');
    $this->assertFalse($event_subscriber->hasEventFired(EntityTypeEvents::DELETE), 'Entity type delete was not dispatched yet.');
    $this->entityManager->onEntityTypeDelete($entity_type);
    $this->assertTrue($event_subscriber->hasEventFired(EntityTypeEvents::DELETE), 'Entity type delete event successfully dispatched.');
  }

  /**
   * Tests updating entity schema and creating a base field.
   *
   * This tests updating entity schema and creating a base field at the same
   * time when there are no existing entities.
   */
  public function testEntityTypeSchemaUpdateAndBaseFieldCreateWithoutData() {
    $this->updateEntityTypeToRevisionable();
    $this->addBaseField();
    $message = 'Successfully updated entity schema and created base field at the same time.';
    // Entity type updates create base fields as well, thus make sure doing both
    // at the same time does not lead to errors due to the base field being
    // created twice.
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->pass($message);
    }
    catch (\Exception $e) {
      $this->fail($message);
      throw $e;
    }
  }

  /**
   * Tests updating entity schema and creating a revisionable base field.
   *
   * This tests updating entity schema and creating a revisionable base field
   * at the same time when there are no existing entities.
   */
  public function testEntityTypeSchemaUpdateAndRevisionableBaseFieldCreateWithoutData() {
    $this->updateEntityTypeToRevisionable();
    $this->addRevisionableBaseField();
    $message = 'Successfully updated entity schema and created revisionable base field at the same time.';
    // Entity type updates create base fields as well, thus make sure doing both
    // at the same time does not lead to errors due to the base field being
    // created twice.
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->pass($message);
    }
    catch (\Exception $e) {
      $this->fail($message);
      throw $e;
    }
  }

  /**
   * Tests ::applyEntityUpdate() and ::applyFieldUpdate().
   */
  public function testSingleActionCalls() {
    // Ensure that the methods return FALSE when called with bogus information.
    $this->assertFalse($this->entityDefinitionUpdateManager->applyEntityUpdate(EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED, 'foo'), 'Calling applyEntityUpdate() with a non-existent entity returns FALSE.');
    $this->assertFalse($this->entityDefinitionUpdateManager->applyFieldUpdate(EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED, 'foo', 'bar'), 'Calling applyFieldUpdate() with a non-existent entity returns FALSE.');
    $this->assertFalse($this->entityDefinitionUpdateManager->applyFieldUpdate(EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED, 'entity_test_update', 'bar'), 'Calling applyFieldUpdate() with a non-existent field returns FALSE.');
    $this->assertFalse($this->entityDefinitionUpdateManager->applyEntityUpdate(EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED, 'entity_test_update'), 'Calling applyEntityUpdate() with an $op that is not applicable to the entity type returns FALSE.');
    $this->assertFalse($this->entityDefinitionUpdateManager->applyFieldUpdate(EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED, 'entity_test_update', 'new_base_field'), 'Calling applyFieldUpdate() with an $op that is not applicable to the field returns FALSE.');

    // Create a new base field.
    $this->addRevisionableBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->applyFieldUpdate(EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED, 'entity_test_update', 'new_base_field'), 'Calling applyFieldUpdate() correctly returns TRUE.');
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' has been created on the 'entity_test_update' table.");
    // Make the entity type revisionable.
    $this->updateEntityTypeToRevisionable();
    $this->assertTrue($this->entityDefinitionUpdateManager->applyEntityUpdate(EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED, 'entity_test_update'), 'Calling applyEntityUpdate() correctly returns TRUE.');
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update_revision'), "The 'entity_test_update_revision' table has been created.");
  }

  /**
   * Ensures that a new field and index on a shared table are created.
   *
   * @see Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema::createSharedTableSchema
   */
  public function testCreateFieldAndIndexOnSharedTable() {
    $this->addBaseField();
    $this->addBaseFieldIndex();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' has been created on the 'entity_test_update' table.");
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), "New index 'entity_test_update_field__new_base_field' has been created on the 'entity_test_update' table.");
    // Check index size in for MySQL.
    if (Database::getConnection()->driver() == 'mysql') {
      $result = Database::getConnection()->query('SHOW INDEX FROM {entity_test_update} WHERE key_name = \'entity_test_update_field__new_base_field\' and column_name = \'new_base_field\'')->fetchObject();
      $this->assertEqual(191, $result->Sub_part, 'The index length has been restricted to 191 characters for UTF8MB4 compatibility.');
    }
  }

  /**
   * Ensures that a new entity level index is created when data exists.
   *
   * @see Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema::onEntityTypeUpdate
   */
  public function testCreateIndexUsingEntityStorageSchemaWithData() {
    // Save an entity.
    $name = $this->randomString();
    $storage = $this->entityManager->getStorage('entity_test_update');
    $entity = $storage->create(array('name' => $name));
    $entity->save();

    // Create an index.
    $indexes = array(
      'entity_test_update__type_index' => array('type'),
    );
    $this->state->set('entity_test_update.additional_entity_indexes', $indexes);
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__type_index'), "New index 'entity_test_update__type_index' has been created on the 'entity_test_update' table.");
    // Check index size in for MySQL.
    if (Database::getConnection()->driver() == 'mysql') {
      $result = Database::getConnection()->query('SHOW INDEX FROM {entity_test_update} WHERE key_name = \'entity_test_update__type_index\' and column_name = \'type\'')->fetchObject();
      $this->assertEqual(191, $result->Sub_part, 'The index length has been restricted to 191 characters for UTF8MB4 compatibility.');
    }
  }

}
