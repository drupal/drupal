<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Entity\EntityDefinitionUpdateManager;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Field\FieldPurger;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\EntityTestHelper;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests EntityDefinitionUpdateManager functionality.
 */
#[CoversClass(EntityDefinitionUpdateManager::class)]
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityDefinitionUpdateBundleTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_update', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->database = $this->container->get('database');

    // Install every entity type's schema that wasn't installed in the parent
    // method.
    foreach (array_diff_key($this->entityTypeManager->getDefinitions(), array_flip(['user', 'entity_test'])) as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }
  }

  /**
   * Tests creating, updating, and deleting a bundle field if no entities exist.
   */
  public function testBundleFieldCreateUpdateDeleteWithoutData(): void {
    // Add a bundle field, ensure the update manager reports it, and the update
    // creates its schema.
    $this->addBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new bundle field field needs to be installed.', strip_tags((string) $changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table created for new_bundle_field.');

    // Update the type of the base field from 'string' to 'text', ensure the
    // update manager reports it, and the update adjusts the schema
    // accordingly.
    $this->modifyBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new bundle field field needs to be updated.', strip_tags((string) $changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update__new_bundle_field', 'new_bundle_field_format'), 'Format column created in dedicated table for new_base_field.');

    // Remove the bundle field, ensure the update manager reports it, and the
    // update deletes the schema.
    $this->removeBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new bundle field field needs to be uninstalled.', strip_tags((string) $changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table deleted for new_bundle_field.');
  }

  /**
   * Tests creating and deleting a bundle field if entities exist.
   *
   * This tests deletion when there are existing entities, but non-existent data
   * for the field being deleted.
   *
   * @see testBundleFieldDeleteWithExistingData()
   */
  public function testBundleFieldCreateDeleteWithExistingEntities(): void {
    // Save an entity.
    $name = $this->randomString();
    $storage = $this->entityTypeManager->getStorage('entity_test_update');
    $entity = $storage->create(['name' => $name]);
    $entity->save();

    // Add a bundle field and run the update. Ensure the bundle field's table
    // is created and the prior saved entity data is still there.
    $this->addBundleField();
    $this->applyEntityUpdates();
    $schema_handler = $this->database->schema();
    $this->assertTrue($schema_handler->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table created for new_bundle_field.');
    $entity = $this->entityTypeManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertSame($name, $entity->name->value, 'Entity data preserved during field creation.');

    // Remove the base field and run the update. Ensure the bundle field's
    // table is deleted and the prior saved entity data is still there.
    $this->removeBundleField();
    $this->applyEntityUpdates();
    $this->assertFalse($schema_handler->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table deleted for new_bundle_field.');
    $entity = $this->entityTypeManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertSame($name, $entity->name->value, 'Entity data preserved during field deletion.');

    // Test that required columns are created as 'not null'.
    $this->addBundleField('shape_required');
    $this->applyEntityUpdates();
    $message = 'The new_bundle_field_shape column is not nullable.';
    $values = [
      'bundle' => $entity->bundle(),
      'deleted' => 0,
      'entity_id' => $entity->id(),
      'revision_id' => $entity->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'delta' => 0,
      'new_bundle_field_color' => $this->randomString(),
    ];
    try {
      // Try to insert a record without providing a value for the 'not null'
      // column. This should fail.
      $this->database->insert('entity_test_update__new_bundle_field')
        ->fields($values)
        ->execute();
      $this->fail($message);
    }
    catch (IntegrityConstraintViolationException) {
      // Now provide a value for the 'not null' column. This is expected to
      // succeed.
      $values['new_bundle_field_shape'] = $this->randomString();
      $this->database->insert('entity_test_update__new_bundle_field')
        ->fields($values)
        ->execute();
    }
  }

  /**
   * Tests deleting a bundle field when it has existing data.
   */
  public function testBundleFieldDeleteWithExistingData(): void {
    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('entity_test_update');
    $schema_handler = $this->database->schema();

    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->applyEntityUpdates();

    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping();
    $storage_definition = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('entity_test_update')['new_bundle_field'];

    // Check that the bundle field has a dedicated table.
    $dedicated_table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
    $this->assertTrue($schema_handler->tableExists($dedicated_table_name), 'The bundle field uses a dedicated table.');

    // Save an entity with the bundle field populated.
    EntityTestHelper::createBundle('custom');
    $entity = $storage->create(['type' => 'test_bundle', 'new_bundle_field' => 'foo']);
    $entity->save();

    // Remove the bundle field and apply updates.
    $this->removeBundleField();
    $this->applyEntityUpdates();

    // Check that the table of the bundle field has been renamed to use a
    // 'deleted' table name.
    $this->assertFalse($schema_handler->tableExists($dedicated_table_name), 'The dedicated table of the bundle field no longer exists.');

    $dedicated_deleted_table_name = $table_mapping->getDedicatedDataTableName($storage_definition, TRUE);
    $this->assertTrue($schema_handler->tableExists($dedicated_deleted_table_name), 'The dedicated table of the bundle fields has been renamed to use the "deleted" name.');

    // Check that the deleted field's data is preserved in the dedicated
    // 'deleted' table.
    $result = $this->database->select($dedicated_deleted_table_name, 't')
      ->fields('t')
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $result);

    $expected = [
      'bundle' => $entity->bundle(),
      'deleted' => '1',
      'entity_id' => $entity->id(),
      'revision_id' => $entity->id(),
      'langcode' => $entity->language()->getId(),
      'delta' => '0',
      'new_bundle_field_value' => $entity->new_bundle_field->value,
    ];
    // Use assertEquals and not assertSame here to prevent that a different
    // sequence of the columns in the table will affect the check.
    $this->assertEquals($expected, (array) $result[0]);

    // Check that the field definition is marked for purging.
    $deleted_field_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldDefinitions();
    $this->assertArrayHasKey($storage_definition->getUniqueIdentifier(), $deleted_field_definitions, 'The bundle field is marked for purging.');

    // Check that the field storage definition is marked for purging.
    $deleted_storage_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldStorageDefinitions();
    $this->assertArrayHasKey($storage_definition->getUniqueStorageIdentifier(), $deleted_storage_definitions, 'The bundle field storage is marked for purging.');

    // Purge field data, and check that the storage definition has been
    // completely removed once the data is purged.
    \Drupal::service(FieldPurger::class)->purgeBatch(10);
    $deleted_field_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldDefinitions();
    $this->assertEmpty($deleted_field_definitions, 'The bundle field has been deleted.');
    $deleted_storage_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldStorageDefinitions();
    $this->assertEmpty($deleted_storage_definitions, 'The bundle field storage has been deleted.');
    $this->assertFalse($schema_handler->tableExists($dedicated_deleted_table_name), 'The dedicated table of the bundle field has been removed.');
  }

  /**
   * Tests updating a bundle field when it has existing data.
   */
  public function testBundleFieldUpdateWithExistingData(): void {
    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->applyEntityUpdates();

    // Save an entity with the bundle field populated.
    EntityTestHelper::createBundle('custom');
    $this->entityTypeManager
      ->getStorage('entity_test_update')
      ->create(['type' => 'test_bundle', 'new_bundle_field' => 'foo'])
      ->save();

    // Change the field's field type and apply updates. It's expected to
    // throw an exception.
    $this->modifyBundleField();
    try {
      $this->applyEntityUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests updating a bundle field when the entity type schema has changed.
   */
  public function testBundleFieldUpdateWithEntityTypeSchemaUpdate(): void {
    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->applyEntityUpdates();

    // Update the entity type schema to revisionable but don't run the updates
    // yet.
    $this->updateEntityTypeToRevisionable();

    // Perform a no-op update on the bundle field, which should work because
    // both the storage and the storage schema are using the last installed
    // entity type definition.
    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $entity_definition_update_manager->updateFieldStorageDefinition($entity_definition_update_manager->getFieldStorageDefinition('new_bundle_field', 'entity_test_update'));
  }

}
