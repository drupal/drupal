<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests the default entity storage schema handler.
 *
 * @group Entity
 */
class EntitySchemaTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test_update'];

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('entity_test_update');
    $this->database = $this->container->get('database');
  }

  /**
   * Tests the custom bundle field creation and deletion.
   */
  public function testCustomFieldCreateDelete() {
    // Install the module which adds the field.
    $this->installModule('entity_schema_test');
    $storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_update');
    $this->assertNotNull($storage_definitions['custom_base_field'], 'Base field definition found.');
    $this->assertNotNull($storage_definitions['custom_bundle_field'], 'Bundle field definition found.');

    // Make sure the field schema can be created.
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionCreate($storage_definitions['custom_base_field']);
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionCreate($storage_definitions['custom_bundle_field']);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->entityTypeManager->getStorage('entity_test_update')->getTableMapping();
    $base_table = current($table_mapping->getTableNames());
    $base_column = current($table_mapping->getColumnNames('custom_base_field'));
    $this->assertTrue($this->database->schema()->fieldExists($base_table, $base_column), 'Table column created');
    $table = $table_mapping->getDedicatedDataTableName($storage_definitions['custom_bundle_field']);
    $this->assertTrue($this->database->schema()->tableExists($table), 'Table created');

    // Make sure the field schema can be deleted.
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionDelete($storage_definitions['custom_base_field']);
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionDelete($storage_definitions['custom_bundle_field']);
    $this->assertFalse($this->database->schema()->fieldExists($base_table, $base_column), 'Table column dropped');
    $this->assertFalse($this->database->schema()->tableExists($table), 'Table dropped');
  }

  /**
   * Updates the entity type definition.
   *
   * @param bool $alter
   *   Whether the original definition should be altered or not.
   */
  protected function updateEntityType($alter) {
    $this->state->set('entity_schema_update', $alter);
    $updated_entity_type = $this->getUpdatedEntityTypeDefinition($alter, $alter);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions($alter, $alter);
    $this->container->get('entity.definition_update_manager')->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions);
  }

  /**
   * Tests that entity schema responds to changes in the entity type definition.
   */
  public function testEntitySchemaUpdate() {
    $this->installModule('entity_schema_test');
    $storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_update');
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionCreate($storage_definitions['custom_base_field']);
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionCreate($storage_definitions['custom_bundle_field']);
    $schema_handler = $this->database->schema();
    $tables = ['entity_test_update', 'entity_test_update_revision', 'entity_test_update_data', 'entity_test_update_revision_data'];
    $dedicated_tables = ['entity_test_update__custom_bundle_field', 'entity_test_update_revision__custom_bundle_field'];

    // Initially only the base table and the dedicated field data table should
    // exist.
    foreach ($tables as $index => $table) {
      $this->assertEquals(!$index, $schema_handler->tableExists($table), new FormattableMarkup('Entity schema correct for the @table table.', ['@table' => $table]));
    }
    $this->assertTrue($schema_handler->tableExists($dedicated_tables[0]), new FormattableMarkup('Field schema correct for the @table table.', ['@table' => $table]));

    // Update the entity type definition and check that the entity schema now
    // supports translations and revisions.
    $this->updateEntityType(TRUE);
    foreach ($tables as $table) {
      $this->assertTrue($schema_handler->tableExists($table), new FormattableMarkup('Entity schema correct for the @table table.', ['@table' => $table]));
    }
    foreach ($dedicated_tables as $table) {
      $this->assertTrue($schema_handler->tableExists($table), new FormattableMarkup('Field schema correct for the @table table.', ['@table' => $table]));
    }

    // Revert changes and check that the entity schema now does not support
    // neither translations nor revisions.
    $this->updateEntityType(FALSE);
    foreach ($tables as $index => $table) {
      $this->assertEquals(!$index, $schema_handler->tableExists($table), new FormattableMarkup('Entity schema correct for the @table table.', ['@table' => $table]));
    }
    $this->assertTrue($schema_handler->tableExists($dedicated_tables[0]), new FormattableMarkup('Field schema correct for the @table table.', ['@table' => $table]));
  }

  /**
   * Tests deleting and creating a field that is part of a primary key.
   *
   * @param string $entity_type_id
   *   The ID of the entity type whose schema is being tested.
   * @param string $field_name
   *   The name of the field that is being re-installed.
   *
   * @dataProvider providerTestPrimaryKeyUpdate
   */
  public function testPrimaryKeyUpdate($entity_type_id, $field_name) {
    // EntityKernelTestBase::setUp() already installs the schema for the
    // 'entity_test' entity type.
    if ($entity_type_id !== 'entity_test') {
      $this->installEntitySchema($entity_type_id);
    }

    /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
    $update_manager = $this->container->get('entity.definition_update_manager');
    $entity_type = $update_manager->getEntityType($entity_type_id);

    /* @see \Drupal\Core\Entity\ContentEntityBase::baseFieldDefinitions() */
    switch ($field_name) {
      case 'id':
        $field = BaseFieldDefinition::create('integer')
          ->setLabel('ID')
          ->setReadOnly(TRUE)
          ->setSetting('unsigned', TRUE);
        break;

      case 'revision_id':
        $field = BaseFieldDefinition::create('integer')
          ->setLabel('Revision ID')
          ->setReadOnly(TRUE)
          ->setSetting('unsigned', TRUE);
        break;

      case 'langcode':
        $field = BaseFieldDefinition::create('language')
          ->setLabel('Language');
        if ($entity_type->isRevisionable()) {
          $field->setRevisionable(TRUE);
        }
        if ($entity_type->isTranslatable()) {
          $field->setTranslatable(TRUE);
        }
        break;
    }

    $field
      ->setName($field_name)
      ->setTargetEntityTypeId($entity_type_id)
      ->setProvider($entity_type->getProvider());

    // Build up a map of expected primary keys depending on the entity type
    // configuration.
    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');
    $langcode_key = $entity_type->getKey('langcode');

    $expected = [];
    $expected[$entity_type->getBaseTable()] = [$id_key];
    if ($entity_type->isRevisionable()) {
      $expected[$entity_type->getRevisionTable()] = [$revision_key];
    }
    if ($entity_type->isTranslatable()) {
      $expected[$entity_type->getDataTable()] = [$id_key, $langcode_key];
    }
    if ($entity_type->isRevisionable() && $entity_type->isTranslatable()) {
      $expected[$entity_type->getRevisionDataTable()] = [$revision_key, $langcode_key];
    }

    // First, test explicitly deleting and re-installing a field. Make sure that
    // all primary keys are there to start with.
    $this->assertSame($expected, $this->findPrimaryKeys($entity_type));

    // Then uninstall the field and make sure all primary keys that the field
    // was part of have been updated. Since this is not a valid state of the
    // entity type (for example a revisionable entity type without a revision ID
    // field or a translatable entity type without a language code field) the
    // actual primary keys at this point are irrelevant.
    $update_manager->uninstallFieldStorageDefinition($field);
    $this->assertNotEquals($expected, $this->findPrimaryKeys($entity_type));

    // Finally, reinstall the field and make sure the primary keys have been
    // recreated.
    $update_manager->installFieldStorageDefinition($field->getName(), $entity_type_id, $field->getProvider(), $field);
    $this->assertSame($expected, $this->findPrimaryKeys($entity_type));

    // Now test updating a field without data. This will end up deleting
    // and re-creating the field, similar to the code above.
    $update_manager->updateFieldStorageDefinition($field);
    $this->assertSame($expected, $this->findPrimaryKeys($entity_type));

    // Now test updating a field with data.
    /** @var \Drupal\Core\Entity\FieldableEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    // The schema of ID fields is incorrectly recreated as 'int' instead of
    // 'serial', so we manually have to specify an ID.
    // @todo Remove this in https://www.drupal.org/project/drupal/issues/2928906
    $storage->create(['id' => 1, 'revision_id' => 1])->save();
    $this->assertTrue($storage->countFieldData($field, TRUE));
    $update_manager->updateFieldStorageDefinition($field);
    $this->assertSame($expected, $this->findPrimaryKeys($entity_type));
    $this->assertTrue($storage->countFieldData($field, TRUE));
  }

  /**
   * Finds the primary keys for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type whose primary keys are being fetched.
   *
   * @return array[]
   *   An array where the keys are the table names of the entity type's tables
   *   and the values are a list of the respective primary keys.
   */
  protected function findPrimaryKeys(EntityTypeInterface $entity_type) {
    $base_table = $entity_type->getBaseTable();
    $revision_table = $entity_type->getRevisionTable();
    $data_table = $entity_type->getDataTable();
    $revision_data_table = $entity_type->getRevisionDataTable();

    $schema = $this->database->schema();
    $find_primary_key_columns = new \ReflectionMethod(get_class($schema), 'findPrimaryKeyColumns');
    $find_primary_key_columns->setAccessible(TRUE);

    // Build up a map of primary keys depending on the entity type
    // configuration. If the field that is being removed is part of a table's
    // primary key, we skip the assertion for that table as this represents an
    // intermediate and invalid state of the entity schema.
    $primary_keys[$base_table] = $find_primary_key_columns->invoke($schema, $base_table);
    if ($entity_type->isRevisionable()) {
      $primary_keys[$revision_table] = $find_primary_key_columns->invoke($schema, $revision_table);
    }
    if ($entity_type->isTranslatable()) {
      $primary_keys[$data_table] = $find_primary_key_columns->invoke($schema, $data_table);
    }
    if ($entity_type->isRevisionable() && $entity_type->isTranslatable()) {
      $primary_keys[$revision_data_table] = $find_primary_key_columns->invoke($schema, $revision_data_table);
    }

    return $primary_keys;
  }

  /**
   * Provides test cases for EntitySchemaTest::testPrimaryKeyUpdate()
   *
   * @return array
   *   An array of test cases consisting of an entity type ID and a field name.
   */
  public function providerTestPrimaryKeyUpdate() {
    // Build up test cases for all possible entity type configurations.
    // For each entity type we test reinstalling each field that is part of
    // any table's primary key.
    $tests = [];

    $tests['entity_test:id'] = ['entity_test', 'id'];

    $tests['entity_test_rev:id'] = ['entity_test_rev', 'id'];
    $tests['entity_test_rev:revision_id'] = ['entity_test_rev', 'revision_id'];

    $tests['entity_test_mul:id'] = ['entity_test_mul', 'id'];
    $tests['entity_test_mul:langcode'] = ['entity_test_mul', 'langcode'];

    $tests['entity_test_mulrev:id'] = ['entity_test_mulrev', 'id'];
    $tests['entity_test_mulrev:revision_id'] = ['entity_test_mulrev', 'revision_id'];
    $tests['entity_test_mulrev:langcode'] = ['entity_test_mulrev', 'langcode'];

    return $tests;
  }

  /**
   * {@inheritdoc}
   */
  protected function refreshServices() {
    parent::refreshServices();
    $this->database = $this->container->get('database');
  }

  /**
   * Tests that modifying the UUID field for a translatable entity works.
   */
  public function testModifyingTranslatableColumnSchema() {
    $this->installModule('entity_schema_test');
    $this->updateEntityType(TRUE);
    $fields = ['revision_log', 'uuid'];
    $entity_field_manager = \Drupal::service('entity_field.manager');
    foreach ($fields as $field_name) {
      $original_definition = $entity_field_manager->getBaseFieldDefinitions('entity_test_update')[$field_name];
      $new_definition = clone $original_definition;
      $new_definition->setLabel($original_definition->getLabel() . ', the other one');
      $this->assertTrue($this->entityTypeManager->getStorage('entity_test_update')
        ->requiresFieldDataMigration($new_definition, $original_definition));
    }
  }

  /**
   * Tests fields from an uninstalled module are removed from the schema.
   */
  public function testCleanUpStorageDefinition() {
    // Find all the entity types provided by the entity_test module and install
    // the schema for them.
    $entity_type_ids = [];
    $entities = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entities as $entity_type_id => $definition) {
      if ($definition->getProvider() == 'entity_test') {
        $this->installEntitySchema($entity_type_id);
        $entity_type_ids[] = $entity_type_id;
      }
    }

    // Get a list of all the entities in the schema.
    $key_value_store = \Drupal::keyValue('entity.storage_schema.sql');
    $schema = $key_value_store->getAll();

    // Count the storage definitions provided by the entity_test module, so that
    // after uninstall we can be sure there were some to be deleted.
    $entity_type_id_count = 0;

    foreach (array_keys($schema) as $storage_definition_name) {
      list($entity_type_id, ,) = explode('.', $storage_definition_name);
      if (in_array($entity_type_id, $entity_type_ids)) {
        $entity_type_id_count++;
      }
    }

    // Ensure that there are storage definitions from the entity_test module.
    $this->assertNotEquals(0, $entity_type_id_count, 'There are storage definitions provided by the entity_test module in the schema.');

    // Uninstall the entity_test module.
    $this->container->get('module_installer')->uninstall(['entity_test']);

    // Get a list of all the entities in the schema.
    $key_value_store = \Drupal::keyValue('entity.storage_schema.sql');
    $schema = $key_value_store->getAll();

    // Count the storage definitions that come from entity types provided by
    // the entity_test module.
    $entity_type_id_count = 0;

    foreach (array_keys($schema) as $storage_definition_name) {
      list($entity_type_id, ,) = explode('.', $storage_definition_name);
      if (in_array($entity_type_id, $entity_type_ids)) {
        $entity_type_id_count++;
      }
    }

    // Ensure that all storage definitions have been removed from the schema.
    $this->assertEquals(0, $entity_type_id_count, 'After uninstalling entity_test module the schema should not contains fields from entities provided by the module.');
  }

  /**
   * Tests the installed storage schema for identifier fields.
   */
  public function testIdentifierSchema() {
    $this->installEntitySchema('entity_test_rev');

    $key_value_store = \Drupal::keyValue('entity.storage_schema.sql');
    $id_schema = $key_value_store->get('entity_test_rev.field_schema_data.id', []);
    $revision_id_schema = $key_value_store->get('entity_test_rev.field_schema_data.revision_id', []);

    $expected_id_schema = [
      'entity_test_rev' => [
        'fields' => [
          'id' => [
            'type' => 'serial',
            'unsigned' => TRUE,
            'size' => 'normal',
            'not null' => TRUE,
          ],
        ],
      ],
      'entity_test_rev_revision' => [
        'fields' => [
          'id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'size' => 'normal',
            'not null' => TRUE,
          ],
        ],
      ],
    ];
    $this->assertEquals($expected_id_schema, $id_schema);

    $expected_revision_id_schema = [
      'entity_test_rev' => [
        'fields' => [
          'revision_id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'size' => 'normal',
            'not null' => FALSE,
          ],
        ],
      ],
      'entity_test_rev_revision' => [
        'fields' => [
          'revision_id' => [
            'type' => 'serial',
            'unsigned' => TRUE,
            'size' => 'normal',
            'not null' => TRUE,
          ],
        ],
      ],
    ];
    $this->assertEquals($expected_revision_id_schema, $revision_id_schema);
  }

}
