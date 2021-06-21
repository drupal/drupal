<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionEvents;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\entity_test_update\Entity\EntityTestUpdate;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests EntityDefinitionUpdateManager functionality.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityDefinitionUpdateManager
 *
 * @group Entity
 */
class EntityDefinitionUpdateTest extends EntityKernelTestBase {

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
   * Modules to enable.
   *
   * @var array
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
   * Tests that new entity type definitions are correctly handled.
   */
  public function testNewEntityType() {
    $entity_type_id = 'entity_test_new';
    $schema = $this->database->schema();

    // Check that the "entity_test_new" is not defined.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $this->assertFalse(isset($entity_types[$entity_type_id]), 'The "entity_test_new" entity type does not exist.');
    $this->assertFalse($schema->tableExists($entity_type_id), 'Schema for the "entity_test_new" entity type does not exist.');

    // Check that the "entity_test_new" is now defined and the related schema
    // has been created.
    $this->enableNewEntityType();
    $entity_types = $this->entityTypeManager->getDefinitions();
    $this->assertTrue(isset($entity_types[$entity_type_id]), 'The "entity_test_new" entity type exists.');
    $this->assertTrue($schema->tableExists($entity_type_id), 'Schema for the "entity_test_new" entity type has been created.');
  }

  /**
   * Tests when no definition update is needed.
   */
  public function testNoUpdates() {
    // Ensure that the definition update manager reports no updates.
    $this->assertFalse($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that no updates are needed.');
    $this->assertSame([], $this->entityDefinitionUpdateManager->getChangeSummary(), 'EntityDefinitionUpdateManager reports an empty change summary.');
    $this->assertSame([], $this->entityDefinitionUpdateManager->getChangeList(), 'EntityDefinitionUpdateManager reports an empty change list.');
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
    $expected = [
      'entity_test_update' => [
        t('The %entity_type entity type needs to be updated.', ['%entity_type' => $this->entityTypeManager->getDefinition('entity_test_update')->getLabel()]),
        // The revision key is now defined, so the revision field needs to be
        // created.
        t('The %field_name field needs to be installed.', ['%field_name' => 'Revision ID']),
        t('The %field_name field needs to be installed.', ['%field_name' => 'Default revision']),
      ],
    ];
    $this->assertEquals($expected, $this->entityDefinitionUpdateManager->getChangeSummary(), 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the revision table is created.
    $this->updateEntityTypeToRevisionable(TRUE);
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update_revision'), 'Revision table created for entity_test_update.');
  }

  /**
   * Tests updating entity schema when there are entity storage changes.
   */
  public function testEntityTypeUpdateWithEntityStorageChange() {
    // Update the entity type to be revisionable and try to apply the update.
    // It's expected to throw an exception.
    $entity_type = $this->getUpdatedEntityTypeDefinition(TRUE, FALSE);
    try {
      $this->entityDefinitionUpdateManager->updateEntityType($entity_type);
      $this->fail('EntityStorageException thrown when trying to apply an update that requires shared table schema changes.');
    }
    catch (EntityStorageException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests installing an additional base field while installing an entity type.
   *
   * @covers ::installFieldableEntityType
   */
  public function testInstallAdditionalBaseFieldDuringFieldableEntityTypeInstallation() {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_update');

    // Enable the creation of a new base field during the installation of a
    // fieldable entity type.
    $this->state->set('entity_test_update.install_new_base_field_during_create', TRUE);

    // Install the entity type and check that the additional base field was also
    // installed.
    $this->entityDefinitionUpdateManager->installFieldableEntityType($entity_type, $field_storage_definitions);

    // Check whether the 'new_base_field' field has been installed correctly.
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition('new_base_field', 'entity_test_update');
    $this->assertNotNull($field_storage_definition);
  }

  /**
   * Tests creating a fieldable entity type that doesn't exist in code anymore.
   *
   * @covers ::installFieldableEntityType
   */
  public function testInstallFieldableEntityTypeWithoutInCodeDefinition() {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_update');

    // Remove the entity type definition. This is the same thing as removing the
    // code that defines it.
    $this->deleteEntityType();

    // Install the entity type and check that its tables have been created.
    $this->entityDefinitionUpdateManager->installFieldableEntityType($entity_type, $field_storage_definitions);
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update'), 'The base table of the entity type has been created.');
  }

  /**
   * Tests updating an entity type that doesn't exist in code anymore.
   *
   * @covers ::updateEntityType
   */
  public function testUpdateEntityTypeWithoutInCodeDefinition() {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');

    // Remove the entity type definition. This is the same thing as removing the
    // code that defines it.
    $this->deleteEntityType();

    // Add an entity index, update the entity type and check that the index has
    // been created.
    $this->addEntityIndex();
    $this->entityDefinitionUpdateManager->updateEntityType($entity_type);

    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index created.');
  }

  /**
   * Tests updating a fieldable entity type that doesn't exist in code anymore.
   *
   * @covers ::updateFieldableEntityType
   */
  public function testUpdateFieldableEntityTypeWithoutInCodeDefinition() {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_update');

    // Remove the entity type definition. This is the same thing as removing the
    // code that defines it.
    $this->deleteEntityType();

    // Rename the base table, update the fieldable entity type and check that
    // the table has been renamed.
    $entity_type->set('base_table', 'entity_test_update_new');
    $this->entityDefinitionUpdateManager->updateFieldableEntityType($entity_type, $field_storage_definitions);

    $this->assertTrue($this->database->schema()->tableExists('entity_test_update_new'), 'The base table has been renamed.');
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update'), 'The old base table does not exist anymore.');
  }

  /**
   * Tests uninstalling an entity type that doesn't exist in code anymore.
   *
   * @covers ::uninstallEntityType
   */
  public function testUninstallEntityTypeWithoutInCodeDefinition() {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_update');

    // Remove the entity type definition. This is the same thing as removing the
    // code that defines it.
    $this->deleteEntityType();

    // Now uninstall it and check that the tables have been removed.
    $this->entityDefinitionUpdateManager->uninstallEntityType($entity_type);
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update'), 'Base table for entity_test_update does not exist anymore.');
  }

  /**
   * Tests creating, updating, and deleting a base field if no entities exist.
   */
  public function testBaseFieldCreateUpdateDeleteWithoutData() {
    // Add a base field, ensure the update manager reports it, and the update
    // creates its schema.
    $this->addBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new base field field needs to be installed.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Column created in shared table for new_base_field.');

    // Add an index on the base field, ensure the update manager reports it,
    // and the update creates it.
    $this->addBaseFieldIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new base field field needs to be updated.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), 'Index created.');

    // Remove the above index, ensure the update manager reports it, and the
    // update deletes it.
    $this->removeBaseFieldIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new base field field needs to be updated.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertFalse($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), 'Index deleted.');

    // Update the type of the base field from 'string' to 'text', ensure the
    // update manager reports it, and the update adjusts the schema
    // accordingly.
    $this->modifyBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new base field field needs to be updated.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Original column deleted in shared table for new_base_field.');
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field__value'), 'Value column created in shared table for new_base_field.');
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field__format'), 'Format column created in shared table for new_base_field.');

    // Remove the base field, ensure the update manager reports it, and the
    // update deletes the schema.
    $this->removeBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new base field field needs to be uninstalled.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field_value'), 'Value column deleted from shared table for new_base_field.');
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field_format'), 'Format column deleted from shared table for new_base_field.');
  }

  /**
   * Tests creating, updating, and deleting a base field with no label set.
   *
   * See testBaseFieldCreateUpdateDeleteWithoutData() for more details
   */
  public function testBaseFieldWithoutLabelCreateUpdateDelete() {
    // Add a base field, ensure the update manager reports it with the
    // field id.
    $this->addBaseField('string', 'entity_test_update', FALSE, FALSE);
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The new_base_field field needs to be installed.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();

    // Add an index on the base field, ensure the update manager reports it with
    // the field id.
    $this->addBaseFieldIndex();
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The new_base_field field needs to be updated.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();

    // Remove the base field, ensure the update manager reports it with the
    // field id.
    $this->removeBaseField();
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The new_base_field field needs to be uninstalled.', strip_tags($changes['entity_test_update'][0]));
  }

  /**
   * Tests creating, updating, and deleting a bundle field if no entities exist.
   */
  public function testBundleFieldCreateUpdateDeleteWithoutData() {
    // Add a bundle field, ensure the update manager reports it, and the update
    // creates its schema.
    $this->addBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new bundle field field needs to be installed.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table created for new_bundle_field.');

    // Update the type of the base field from 'string' to 'text', ensure the
    // update manager reports it, and the update adjusts the schema
    // accordingly.
    $this->modifyBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new bundle field field needs to be updated.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update__new_bundle_field', 'new_bundle_field_format'), 'Format column created in dedicated table for new_base_field.');

    // Remove the bundle field, ensure the update manager reports it, and the
    // update deletes the schema.
    $this->removeBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $changes = $this->entityDefinitionUpdateManager->getChangeSummary();
    $this->assertCount(1, $changes['entity_test_update']);
    $this->assertEquals('The A new bundle field field needs to be uninstalled.', strip_tags($changes['entity_test_update'][0]));
    $this->applyEntityUpdates();
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table deleted for new_bundle_field.');
  }

  /**
   * Tests creating and deleting a base field if entities exist.
   *
   * This tests deletion when there are existing entities, but non-existent data
   * for the field being deleted.
   *
   * @see testBaseFieldDeleteWithExistingData()
   */
  public function testBaseFieldCreateDeleteWithExistingEntities() {
    // Save an entity.
    $name = $this->randomString();
    $storage = $this->entityTypeManager->getStorage('entity_test_update');
    $entity = $storage->create(['name' => $name]);
    $entity->save();

    // Add a base field and run the update. Ensure the base field's column is
    // created and the prior saved entity data is still there.
    $this->addBaseField();
    $this->applyEntityUpdates();
    $schema_handler = $this->database->schema();
    $this->assertTrue($schema_handler->fieldExists('entity_test_update', 'new_base_field'), 'Column created in shared table for new_base_field.');
    $entity = $this->entityTypeManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertSame($name, $entity->name->value, 'Entity data preserved during field creation.');

    // Remove the base field and run the update. Ensure the base field's column
    // is deleted and the prior saved entity data is still there.
    $this->removeBaseField();
    $this->applyEntityUpdates();
    $this->assertFalse($schema_handler->fieldExists('entity_test_update', 'new_base_field'), 'Column deleted from shared table for new_base_field.');
    $entity = $this->entityTypeManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertSame($name, $entity->name->value, 'Entity data preserved during field deletion.');

    // Add a base field with a required property and run the update. Ensure
    // 'not null' is not applied and thus no exception is thrown.
    $this->addBaseField('shape_required');
    $this->applyEntityUpdates();
    $assert = $schema_handler->fieldExists('entity_test_update', 'new_base_field__shape') && $schema_handler->fieldExists('entity_test_update', 'new_base_field__color');
    $this->assertTrue($assert, 'Columns created in shared table for new_base_field.');

    // Recreate the field after emptying the base table and check that its
    // columns are not 'not null'.
    // @todo Revisit this test when allowing for required storage field
    //   definitions. See https://www.drupal.org/node/2390495.
    $entity->delete();
    $this->removeBaseField();
    $this->applyEntityUpdates();
    $this->assertFalse($schema_handler->fieldExists('entity_test_update', 'new_base_field__shape'), 'Shape column should be removed from the shared table for new_base_field.');
    $this->assertFalse($schema_handler->fieldExists('entity_test_update', 'new_base_field__color'), 'Color column should be removed from the shared table for new_base_field.');
    $this->addBaseField('shape_required');
    $this->applyEntityUpdates();
    $assert = $schema_handler->fieldExists('entity_test_update', 'new_base_field__shape') && $schema_handler->fieldExists('entity_test_update', 'new_base_field__color');
    $this->assertTrue($assert, 'Columns created again in shared table for new_base_field.');
    $entity = $storage->create(['name' => $name]);
    $entity->save();
  }

  /**
   * Tests creating and deleting a bundle field if entities exist.
   *
   * This tests deletion when there are existing entities, but non-existent data
   * for the field being deleted.
   *
   * @see testBundleFieldDeleteWithExistingData()
   */
  public function testBundleFieldCreateDeleteWithExistingEntities() {
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
    catch (IntegrityConstraintViolationException $e) {
      // Now provide a value for the 'not null' column. This is expected to
      // succeed.
      $values['new_bundle_field_shape'] = $this->randomString();
      $this->database->insert('entity_test_update__new_bundle_field')
        ->fields($values)
        ->execute();
    }
  }

  /**
   * Tests deleting a base field when it has existing data.
   *
   * @dataProvider baseFieldDeleteWithExistingDataTestCases
   */
  public function testBaseFieldDeleteWithExistingData($entity_type_id, $create_entity_revision, $base_field_revisionable, $create_entity_translation) {
    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('ro')->save();

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $schema_handler = $this->database->schema();

    // Create an entity without the base field, to ensure NULL values are not
    // added to the dedicated table storage to be purged.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create();
    $entity->save();

    // Add the base field and run the update.
    $this->addBaseField('string', $entity_type_id, $base_field_revisionable, TRUE, $create_entity_translation);
    $this->applyEntityUpdates();

    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping();
    $storage_definition = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions($entity_type_id)['new_base_field'];

    // Save an entity with the base field populated.
    $entity = $storage->create(['new_base_field' => 'foo']);
    $entity->save();

    if ($create_entity_translation) {
      $translation = $entity->addTranslation('ro', ['new_base_field' => 'foo-ro']);
      $translation->save();
    }

    if ($create_entity_revision) {
      $entity->setNewRevision(TRUE);
      $entity->isDefaultRevision(FALSE);
      $entity->new_base_field = 'bar';
      $entity->save();

      if ($create_entity_translation) {
        $translation = $entity->getTranslation('ro');
        $translation->new_base_field = 'bar-ro';
        $translation->save();
      }
    }

    // Remove the base field and apply updates.
    $this->removeBaseField($entity_type_id);
    $this->applyEntityUpdates();

    // Check that the base field's column is deleted.
    $this->assertFalse($schema_handler->fieldExists($entity_type_id, 'new_base_field'), 'Column deleted from shared table for new_base_field.');

    // Check that a dedicated 'deleted' table was created for the deleted base
    // field.
    $dedicated_deleted_table_name = $table_mapping->getDedicatedDataTableName($storage_definition, TRUE);
    $this->assertTrue($schema_handler->tableExists($dedicated_deleted_table_name), 'A dedicated table was created for the deleted new_base_field.');

    $expected[] = [
      'bundle' => $entity->bundle(),
      'deleted' => '1',
      'entity_id' => '2',
      'revision_id' => '2',
      'langcode' => 'en',
      'delta' => '0',
      'new_base_field_value' => 'foo',
    ];

    if ($create_entity_translation) {
      $expected[] = [
        'bundle' => $entity->bundle(),
        'deleted' => '1',
        'entity_id' => '2',
        'revision_id' => '2',
        'langcode' => 'ro',
        'delta' => '0',
        'new_base_field_value' => 'foo-ro',
      ];
    }

    // Check that the deleted field's data is preserved in the dedicated
    // 'deleted' table.
    $result = $this->database->select($dedicated_deleted_table_name, 't')
      ->fields('t')
      ->orderBy('revision_id', 'ASC')
      ->orderBy('langcode', 'ASC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertSameSize($expected, $result);

    // Use assertEquals and not assertSame here to prevent that a different
    // sequence of the columns in the table will affect the check.
    $this->assertEquals($expected, $result);

    if ($create_entity_revision) {
      $dedicated_deleted_revision_table_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, TRUE);
      $this->assertTrue($schema_handler->tableExists($dedicated_deleted_revision_table_name), 'A dedicated revision table was created for the deleted new_base_field.');

      if ($base_field_revisionable) {
        $expected[] = [
          'bundle' => $entity->bundle(),
          'deleted' => '1',
          'entity_id' => '2',
          'revision_id' => '3',
          'langcode' => 'en',
          'delta' => '0',
          'new_base_field_value' => 'bar',
        ];

        if ($create_entity_translation) {
          $expected[] = [
            'bundle' => $entity->bundle(),
            'deleted' => '1',
            'entity_id' => '2',
            'revision_id' => '3',
            'langcode' => 'ro',
            'delta' => '0',
            'new_base_field_value' => 'bar-ro',
          ];
        }
      }

      $result = $this->database->select($dedicated_deleted_revision_table_name, 't')
        ->fields('t')
        ->orderBy('revision_id', 'ASC')
        ->orderBy('langcode', 'ASC')
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);
      $this->assertSameSize($expected, $result);

      // Use assertEquals and not assertSame here to prevent that a different
      // sequence of the columns in the table will affect the check.
      $this->assertEquals($expected, $result);
    }

    // Check that the field storage definition is marked for purging.
    $deleted_storage_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldStorageDefinitions();
    $this->assertArrayHasKey($storage_definition->getUniqueStorageIdentifier(), $deleted_storage_definitions, 'The base field is marked for purging.');

    // Purge field data, and check that the storage definition has been
    // completely removed once the data is purged.
    field_purge_batch(10);
    $deleted_storage_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldStorageDefinitions();
    $this->assertEmpty($deleted_storage_definitions, 'The base field has been deleted.');
    $this->assertFalse($schema_handler->tableExists($dedicated_deleted_table_name), 'A dedicated field table was deleted after new_base_field was purged.');

    if (isset($dedicated_deleted_revision_table_name)) {
      $this->assertFalse($schema_handler->tableExists($dedicated_deleted_revision_table_name), 'A dedicated field revision table was deleted after new_base_field was purged.');
    }
  }

  /**
   * Test cases for ::testBaseFieldDeleteWithExistingData.
   */
  public function baseFieldDeleteWithExistingDataTestCases() {
    return [
      'Non-revisionable, non-translatable entity type' => [
        'entity_test_update',
        FALSE,
        FALSE,
        FALSE,
      ],
      'Non-revisionable, non-translatable custom data table' => [
        'entity_test_mul',
        FALSE,
        FALSE,
        FALSE,
      ],
      'Non-revisionable, non-translatable entity type, revisionable base field' => [
        'entity_test_update',
        FALSE,
        TRUE,
        FALSE,
      ],
      'Non-revisionable, non-translatable custom data table, revisionable base field' => [
        'entity_test_mul',
        FALSE,
        TRUE,
        FALSE,
      ],
      'Revisionable, translatable entity type, non revisionable and non-translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        FALSE,
        FALSE,
      ],
      'Revisionable, translatable entity type, revisionable and non-translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        TRUE,
        FALSE,
      ],
      'Revisionable and non-translatable entity type, revisionable and non-translatable base field' => [
        'entity_test_rev',
        TRUE,
        TRUE,
        FALSE,
      ],
      'Revisionable and non-translatable entity type, non-revisionable and non-translatable base field' => [
        'entity_test_rev',
        TRUE,
        FALSE,
        FALSE,
      ],
      'Revisionable and translatable entity type, non-revisionable and translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        FALSE,
        TRUE,
      ],
      'Revisionable and translatable entity type, revisionable and translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        TRUE,
        TRUE,
      ],
    ];
  }

  /**
   * Tests deleting a bundle field when it has existing data.
   */
  public function testBundleFieldDeleteWithExistingData() {
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
    entity_test_create_bundle('custom');
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
    field_purge_batch(10);
    $deleted_field_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldDefinitions();
    $this->assertEmpty($deleted_field_definitions, 'The bundle field has been deleted.');
    $deleted_storage_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldStorageDefinitions();
    $this->assertEmpty($deleted_storage_definitions, 'The bundle field storage has been deleted.');
    $this->assertFalse($schema_handler->tableExists($dedicated_deleted_table_name), 'The dedicated table of the bundle field has been removed.');
  }

  /**
   * Tests updating a base field when it has existing data.
   */
  public function testBaseFieldUpdateWithExistingData() {
    // Add the base field and run the update.
    $this->addBaseField();
    $this->applyEntityUpdates();

    // Save an entity with the base field populated.
    $this->entityTypeManager->getStorage('entity_test_update')->create(['new_base_field' => 'foo'])->save();

    // Change the field's field type and apply updates. It's expected to
    // throw an exception.
    $this->modifyBaseField();
    try {
      $this->applyEntityUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests updating a bundle field when it has existing data.
   */
  public function testBundleFieldUpdateWithExistingData() {
    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->applyEntityUpdates();

    // Save an entity with the bundle field populated.
    entity_test_create_bundle('custom');
    $this->entityTypeManager->getStorage('entity_test_update')->create(['type' => 'test_bundle', 'new_bundle_field' => 'foo'])->save();

    // Change the field's field type and apply updates. It's expected to
    // throw an exception.
    $this->modifyBundleField();
    try {
      $this->applyEntityUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests updating a bundle field when the entity type schema has changed.
   */
  public function testBundleFieldUpdateWithEntityTypeSchemaUpdate() {
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

  /**
   * Tests creating and deleting a multi-field index when there are no existing entities.
   */
  public function testEntityIndexCreateDeleteWithoutData() {
    // Add an entity index and ensure the update manager reports that as an
    // update to the entity type.
    $this->addEntityIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = [
      'entity_test_update' => [
        t('The %entity_type entity type needs to be updated.', ['%entity_type' => $this->entityTypeManager->getDefinition('entity_test_update')->getLabel()]),
      ],
    ];
    $this->assertEquals($expected, $this->entityDefinitionUpdateManager->getChangeSummary(), 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the new index is created.
    $entity_type = \Drupal::entityTypeManager()->getDefinition('entity_test_update');
    $original = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition('entity_test_update');
    \Drupal::service('entity_type.listener')->onEntityTypeUpdate($entity_type, $original);
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index created.');

    // Remove the index and ensure the update manager reports that as an
    // update to the entity type.
    $this->removeEntityIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = [
      'entity_test_update' => [
        t('The %entity_type entity type needs to be updated.', ['%entity_type' => $this->entityTypeManager->getDefinition('entity_test_update')->getLabel()]),
      ],
    ];
    $this->assertEquals($expected, $this->entityDefinitionUpdateManager->getChangeSummary(), 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the index is deleted.
    $entity_type = \Drupal::entityTypeManager()->getDefinition('entity_test_update');
    $original = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition('entity_test_update');
    \Drupal::service('entity_type.listener')->onEntityTypeUpdate($entity_type, $original);
    $this->assertFalse($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index deleted.');

    // Test that composite indexes are handled correctly when dropping and
    // re-creating one of their columns.
    $this->addEntityIndex();
    $entity_type = \Drupal::entityTypeManager()->getDefinition('entity_test_update');
    $original = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition('entity_test_update');
    \Drupal::service('entity_type.listener')->onEntityTypeUpdate($entity_type, $original);

    $storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition('name', 'entity_test_update');
    $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($storage_definition);
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index created.');
    $this->entityDefinitionUpdateManager->uninstallFieldStorageDefinition($storage_definition);
    $this->assertFalse($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index deleted.');
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('name', 'entity_test_update', 'entity_test', $storage_definition);
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index created again.');
  }

  /**
   * Tests creating a multi-field index when there are existing entities.
   */
  public function testEntityIndexCreateWithData() {
    // Save an entity.
    $name = $this->randomString();
    $entity = $this->entityTypeManager->getStorage('entity_test_update')->create(['name' => $name]);
    $entity->save();

    // Add an entity index, run the update. Ensure that the index is created
    // despite having data.
    $this->addEntityIndex();
    $entity_type = \Drupal::entityTypeManager()->getDefinition('entity_test_update');
    $original = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition('entity_test_update');
    \Drupal::service('entity_type.listener')->onEntityTypeUpdate($entity_type, $original);
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index added.');
  }

  /**
   * Tests entity type and field storage definition events.
   */
  public function testDefinitionEvents() {
    /** @var \Drupal\entity_test\EntityTestDefinitionSubscriber $event_subscriber */
    $event_subscriber = $this->container->get('entity_test.definition.subscriber');
    $event_subscriber->enableEventTracking();
    $event_subscriber->enableLiveDefinitionUpdates();

    // Test field storage definition events.
    $storage_definition = FieldStorageDefinition::create('string')
      ->setName('field_storage_test')
      ->setLabel(new TranslatableMarkup('Field storage test'))
      ->setTargetEntityTypeId('entity_test_rev');

    $this->assertFalse($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::CREATE), 'Entity type create was not dispatched yet.');
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionCreate($storage_definition);
    $this->assertTrue($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::CREATE), 'Entity type create event successfully dispatched.');
    $this->assertTrue($event_subscriber->hasDefinitionBeenUpdated(FieldStorageDefinitionEvents::CREATE), 'Last installed field storage definition was created before the event was fired.');

    // Check that the newly added field can be retrieved from the live field
    // storage definitions.
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions('entity_test_rev');
    $this->assertArrayHasKey('field_storage_test', $field_storage_definitions);

    $updated_storage_definition = clone $storage_definition;
    $updated_storage_definition->setLabel(new TranslatableMarkup('Updated field storage test'));
    $this->assertFalse($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::UPDATE), 'Entity type update was not dispatched yet.');
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionUpdate($updated_storage_definition, $storage_definition);
    $this->assertTrue($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::UPDATE), 'Entity type update event successfully dispatched.');
    $this->assertTrue($event_subscriber->hasDefinitionBeenUpdated(FieldStorageDefinitionEvents::UPDATE), 'Last installed field storage definition was updated before the event was fired.');

    // Check that the updated field can be retrieved from the live field storage
    // definitions.
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions('entity_test_rev');
    $this->assertEquals(new TranslatableMarkup('Updated field storage test'), $field_storage_definitions['field_storage_test']->getLabel());

    $this->assertFalse($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::DELETE), 'Entity type delete was not dispatched yet.');
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionDelete($storage_definition);
    $this->assertTrue($event_subscriber->hasEventFired(FieldStorageDefinitionEvents::DELETE), 'Entity type delete event successfully dispatched.');
    $this->assertTrue($event_subscriber->hasDefinitionBeenUpdated(FieldStorageDefinitionEvents::DELETE), 'Last installed field storage definition was deleted before the event was fired.');

    // Check that the deleted field can no longer be retrieved from the live
    // field storage definitions.
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions('entity_test_rev');
    $this->assertArrayNotHasKey('field_storage_test', $field_storage_definitions);

    // Test entity type events.
    $entity_type = $this->entityTypeManager->getDefinition('entity_test_rev');

    $this->assertFalse($event_subscriber->hasEventFired(EntityTypeEvents::CREATE), 'Entity type create was not dispatched yet.');
    \Drupal::service('entity_type.listener')->onEntityTypeCreate($entity_type);
    $this->assertTrue($event_subscriber->hasEventFired(EntityTypeEvents::CREATE), 'Entity type create event successfully dispatched.');
    $this->assertTrue($event_subscriber->hasDefinitionBeenUpdated(EntityTypeEvents::CREATE), 'Last installed entity type definition was created before the event was fired.');

    $updated_entity_type = clone $entity_type;
    $updated_entity_type->set('label', new TranslatableMarkup('Updated entity test rev'));
    $this->assertFalse($event_subscriber->hasEventFired(EntityTypeEvents::UPDATE), 'Entity type update was not dispatched yet.');
    \Drupal::service('entity_type.listener')->onEntityTypeUpdate($updated_entity_type, $entity_type);
    $this->assertTrue($event_subscriber->hasEventFired(EntityTypeEvents::UPDATE), 'Entity type update event successfully dispatched.');
    $this->assertTrue($event_subscriber->hasDefinitionBeenUpdated(EntityTypeEvents::UPDATE), 'Last installed entity type definition was updated before the event was fired.');

    // Check that the updated definition can be retrieved from the live entity
    // type definitions.
    $entity_type = $this->entityTypeManager->getDefinition('entity_test_rev');
    $this->assertEquals(new TranslatableMarkup('Updated entity test rev'), $entity_type->getLabel());

    $this->assertFalse($event_subscriber->hasEventFired(EntityTypeEvents::DELETE), 'Entity type delete was not dispatched yet.');
    \Drupal::service('entity_type.listener')->onEntityTypeDelete($entity_type);
    $this->assertTrue($event_subscriber->hasEventFired(EntityTypeEvents::DELETE), 'Entity type delete event successfully dispatched.');
    $this->assertTrue($event_subscriber->hasDefinitionBeenUpdated(EntityTypeEvents::DELETE), 'Last installed entity type definition was deleted before the event was fired.');

    // Check that the deleted entity type can no longer be retrieved from the
    // live entity type definitions.
    $this->assertNull($this->entityTypeManager->getDefinition('entity_test_rev', FALSE));
  }

  /**
   * Tests applying single updates.
   */
  public function testSingleActionCalls() {
    $db_schema = $this->database->schema();

    // Ensure that a non-existing entity type cannot be installed.
    $message = 'A non-existing entity type cannot be installed';
    try {
      $this->entityDefinitionUpdateManager->installEntityType(new ContentEntityType(['id' => 'foo']));
      $this->fail($message);
    }
    catch (PluginNotFoundException $e) {
      // Expected exception; just continue testing.
    }

    // Ensure that a field cannot be installed on non-existing entity type.
    $message = 'A field cannot be installed on a non-existing entity type';
    try {
      $storage_definition = BaseFieldDefinition::create('string')
        ->setLabel(t('A new revisionable base field'))
        ->setRevisionable(TRUE);
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition('bar', 'foo', 'entity_test', $storage_definition);
      $this->fail($message);
    }
    catch (PluginNotFoundException $e) {
      // Expected exception; just continue testing.
    }

    // Ensure that installing an existing entity type is a no-op.
    $entity_type = $this->entityDefinitionUpdateManager->getEntityType('entity_test_update');
    $this->entityDefinitionUpdateManager->installEntityType($entity_type);
    $this->assertTrue($db_schema->tableExists('entity_test_update'), 'Installing an existing entity type is a no-op');

    // Create a new base field.
    $this->addRevisionableBaseField();
    $storage_definition = BaseFieldDefinition::create('string')
      ->setLabel(t('A new revisionable base field'))
      ->setRevisionable(TRUE);
    $this->assertFalse($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' does not exist before applying the update.");
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $storage_definition);
    $this->assertTrue($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' has been created on the 'entity_test_update' table.");

    // Ensure that installing an existing field is a no-op.
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $storage_definition);
    $this->assertTrue($db_schema->fieldExists('entity_test_update', 'new_base_field'), 'Installing an existing field is a no-op');

    // Update an existing field schema.
    $this->modifyBaseField();
    $storage_definition = BaseFieldDefinition::create('text')
      ->setName('new_base_field')
      ->setTargetEntityTypeId('entity_test_update')
      ->setLabel(t('A new revisionable base field'))
      ->setRevisionable(TRUE);
    $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($storage_definition);
    $this->assertFalse($db_schema->fieldExists('entity_test_update', 'new_base_field'), "Previous schema for 'new_base_field' no longer exists.");
    $this->assertTrue(
      $db_schema->fieldExists('entity_test_update', 'new_base_field__value') && $db_schema->fieldExists('entity_test_update', 'new_base_field__format'),
      "New schema for 'new_base_field' has been created."
    );

    // Drop an existing field schema.
    $this->entityDefinitionUpdateManager->uninstallFieldStorageDefinition($storage_definition);
    $this->assertFalse(
      $db_schema->fieldExists('entity_test_update', 'new_base_field__value') || $db_schema->fieldExists('entity_test_update', 'new_base_field__format'),
      "The schema for 'new_base_field' has been dropped."
    );

    // Make the entity type revisionable.
    $this->updateEntityTypeToRevisionable();
    $this->assertFalse($db_schema->tableExists('entity_test_update_revision'), "The 'entity_test_update_revision' does not exist before applying the update.");

    $this->updateEntityTypeToRevisionable(TRUE);
    $this->assertTrue($db_schema->tableExists('entity_test_update_revision'), "The 'entity_test_update_revision' table has been created.");
  }

  /**
   * Ensures that a new field and index on a shared table are created.
   *
   * @see Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema::createSharedTableSchema
   */
  public function testCreateFieldAndIndexOnSharedTable() {
    $this->addBaseField();
    $this->addBaseFieldIndex();
    $this->applyEntityUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' has been created on the 'entity_test_update' table.");
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), "New index 'entity_test_update_field__new_base_field' has been created on the 'entity_test_update' table.");
    // Check index size in for MySQL.
    if (Database::getConnection()->driver() == 'mysql') {
      $result = Database::getConnection()->query('SHOW INDEX FROM {entity_test_update} WHERE key_name = \'entity_test_update_field__new_base_field\' and column_name = \'new_base_field\'')->fetchObject();
      $this->assertEquals(191, $result->Sub_part, 'The index length has been restricted to 191 characters for UTF8MB4 compatibility.');
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
    $storage = $this->entityTypeManager->getStorage('entity_test_update');
    $entity = $storage->create(['name' => $name]);
    $entity->save();

    // Create an index.
    $indexes = [
      'entity_test_update__type_index' => ['type'],
    ];
    $this->state->set('entity_test_update.additional_entity_indexes', $indexes);
    $entity_type = \Drupal::entityTypeManager()->getDefinition('entity_test_update');
    $original = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledDefinition('entity_test_update');
    \Drupal::service('entity_type.listener')->onEntityTypeUpdate($entity_type, $original);

    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__type_index'), "New index 'entity_test_update__type_index' has been created on the 'entity_test_update' table.");
    // Check index size in for MySQL.
    if (Database::getConnection()->driver() == 'mysql') {
      $result = Database::getConnection()->query('SHOW INDEX FROM {entity_test_update} WHERE key_name = \'entity_test_update__type_index\' and column_name = \'type\'')->fetchObject();
      $this->assertEquals(191, $result->Sub_part, 'The index length has been restricted to 191 characters for UTF8MB4 compatibility.');
    }
  }

  /**
   * Tests updating a base field when it has existing data.
   */
  public function testBaseFieldEntityKeyUpdateWithExistingData() {
    // Add the base field and run the update.
    $this->addBaseField();
    $this->applyEntityUpdates();

    // Save an entity with the base field populated.
    $this->entityTypeManager->getStorage('entity_test_update')->create(['new_base_field' => $this->randomString()])->save();

    // Save an entity with the base field not populated.
    /** @var \Drupal\entity_test\Entity\EntityTestUpdate $entity */
    $entity = $this->entityTypeManager->getStorage('entity_test_update')->create();
    $entity->save();

    // Promote the base field to an entity key. This will trigger the addition
    // of a NOT NULL constraint.
    $this->makeBaseFieldEntityKey();

    // Field storage CRUD operations use the last installed entity type
    // definition so we need to update it before doing any other field storage
    // updates.
    $this->entityDefinitionUpdateManager->updateEntityType($this->state->get('entity_test_update.entity_type'));

    // Try to apply the update and verify they fail since we have a NULL value.
    $message = 'An error occurs when trying to enabling NOT NULL constraints with NULL data.';
    try {
      $this->applyEntityUpdates();
      $this->fail($message);
    }
    catch (EntityStorageException $e) {
      // Expected exception; just continue testing.
    }

    // Check that the update is correctly applied when no NULL data is left.
    $entity->set('new_base_field', $this->randomString());
    $entity->save();
    $this->applyEntityUpdates();

    // Check that the update actually applied a NOT NULL constraint.
    $entity->set('new_base_field', NULL);
    $message = 'The NOT NULL constraint was correctly applied.';
    try {
      $entity->save();
      $this->fail($message);
    }
    catch (EntityStorageException $e) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Check that field schema is correctly handled with long-named fields.
   */
  public function testLongNameFieldIndexes() {
    $this->addLongNameBaseField();
    $entity_type_id = 'entity_test_update';
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $definitions = EntityTestUpdate::baseFieldDefinitions($entity_type);
    $name = 'new_long_named_entity_reference_base_field';
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition($name, $entity_type_id, 'entity_test', $definitions[$name]);
    $this->assertFalse($this->entityDefinitionUpdateManager->needsUpdates(), 'Entity and field schema data are correctly detected.');
  }

  /**
   * Tests adding a base field with initial values.
   */
  public function testInitialValue() {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $db_schema = $this->database->schema();

    // Create two entities before adding the base field.
    /** @var \Drupal\entity_test\Entity\EntityTestUpdate $entity */
    $storage->create()->save();
    $storage->create()->save();

    // Add a base field with an initial value.
    $this->addBaseField();
    $storage_definition = BaseFieldDefinition::create('string')
      ->setLabel(t('A new base field'))
      ->setInitialValue('test value');

    $this->assertFalse($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' does not exist before applying the update.");
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $storage_definition);
    $this->assertTrue($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' has been created on the 'entity_test_update' table.");

    // Check that the initial values have been applied.
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $entities = $storage->loadMultiple();
    $this->assertEquals('test value', $entities[1]->get('new_base_field')->value);
    $this->assertEquals('test value', $entities[2]->get('new_base_field')->value);
  }

  /**
   * Tests adding a base field with initial values inherited from another field.
   *
   * @dataProvider initialValueFromFieldTestCases
   */
  public function testInitialValueFromField($default_initial_value, $expected_value) {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $db_schema = $this->database->schema();

    // Create two entities before adding the base field.
    /** @var \Drupal\entity_test_update\Entity\EntityTestUpdate $entity */
    $storage->create([
      'name' => 'First entity',
      'test_single_property' => 'test existing value',
    ])->save();

    // The second entity does not have any value for the 'test_single_property'
    // field, allowing us to test the 'default_value' parameter of
    // \Drupal\Core\Field\BaseFieldDefinition::setInitialValueFromField().
    $storage->create([
      'name' => 'Second entity',
    ])->save();

    // Add a base field with an initial value inherited from another field.
    $definitions['new_base_field'] = BaseFieldDefinition::create('string')
      ->setName('new_base_field')
      ->setLabel('A new base field')
      ->setInitialValueFromField('name');
    $definitions['another_base_field'] = BaseFieldDefinition::create('string')
      ->setName('another_base_field')
      ->setLabel('Another base field')
      ->setInitialValueFromField('test_single_property', $default_initial_value);

    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);

    $this->assertFalse($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' does not exist before applying the update.");
    $this->assertFalse($db_schema->fieldExists('entity_test_update', 'another_base_field'), "New field 'another_base_field' does not exist before applying the update.");
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $definitions['new_base_field']);
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('another_base_field', 'entity_test_update', 'entity_test', $definitions['another_base_field']);
    $this->assertTrue($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' has been created on the 'entity_test_update' table.");
    $this->assertTrue($db_schema->fieldExists('entity_test_update', 'another_base_field'), "New field 'another_base_field' has been created on the 'entity_test_update' table.");

    // Check that the initial values have been applied.
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $entities = $storage->loadMultiple();
    $this->assertEquals('First entity', $entities[1]->get('new_base_field')->value);
    $this->assertEquals('Second entity', $entities[2]->get('new_base_field')->value);

    $this->assertEquals('test existing value', $entities[1]->get('another_base_field')->value);
    $this->assertEquals($expected_value, $entities[2]->get('another_base_field')->value);
  }

  /**
   * Test cases for ::testInitialValueFromField.
   */
  public function initialValueFromFieldTestCases() {
    return [
      'literal value' => [
        'test initial value',
        'test initial value',
      ],
      'indexed array' => [
        ['value' => 'test initial value'],
        'test initial value',
      ],
      'empty array' => [
        [],
        NULL,
      ],
      'null' => [
        NULL,
        NULL,
      ],
    ];
  }

  /**
   * Tests the error handling when using initial values from another field.
   */
  public function testInitialValueFromFieldErrorHandling() {
    // Check that setting invalid values for 'initial value from field' doesn't
    // work.
    try {
      $this->addBaseField();
      $storage_definition = BaseFieldDefinition::create('string')
        ->setLabel(t('A new base field'))
        ->setInitialValueFromField('field_that_does_not_exist');
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $storage_definition);
      $this->fail('Using a non-existent field as initial value does not work.');
    }
    catch (FieldException $e) {
      $this->assertEquals('Illegal initial value definition on new_base_field: The field field_that_does_not_exist does not exist.', $e->getMessage());
    }

    try {
      $this->addBaseField();
      $storage_definition = BaseFieldDefinition::create('integer')
        ->setLabel(t('A new base field'))
        ->setInitialValueFromField('name');
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $storage_definition);
      $this->fail('Using a field of a different type as initial value does not work.');
    }
    catch (FieldException $e) {
      $this->assertEquals('Illegal initial value definition on new_base_field: The field types do not match.', $e->getMessage());
    }

    try {
      // Add a base field that will not be stored in the shared tables.
      $initial_field = BaseFieldDefinition::create('string')
        ->setName('initial_field')
        ->setLabel(t('An initial field'))
        ->setCardinality(2);
      $this->state->set('entity_test_update.additional_base_field_definitions', ['initial_field' => $initial_field]);
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition('initial_field', 'entity_test_update', 'entity_test', $initial_field);

      // Now add the base field which will try to use the previously added field
      // as the source of its initial values.
      $new_base_field = BaseFieldDefinition::create('string')
        ->setName('new_base_field')
        ->setLabel(t('A new base field'))
        ->setInitialValueFromField('initial_field');
      $this->state->set('entity_test_update.additional_base_field_definitions', ['initial_field' => $initial_field, 'new_base_field' => $new_base_field]);
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $new_base_field);
      $this->fail('Using a field that is not stored in the shared tables as initial value does not work.');
    }
    catch (FieldException $e) {
      $this->assertEquals('Illegal initial value definition on new_base_field: Both fields have to be stored in the shared entity tables.', $e->getMessage());
    }
  }

  /**
   * @covers ::getEntityTypes
   */
  public function testGetEntityTypes() {
    $entity_type_definitions = $this->entityDefinitionUpdateManager->getEntityTypes();

    // Ensure that we have at least one entity type to check below.
    $this->assertGreaterThanOrEqual(1, count($entity_type_definitions));

    foreach ($entity_type_definitions as $entity_type_id => $entity_type) {
      $this->assertEquals($this->entityDefinitionUpdateManager->getEntityType($entity_type_id), $entity_type);
    }
  }

}
