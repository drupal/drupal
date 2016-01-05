<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntitySchemaTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests adding a custom bundle field.
 *
 * @group system
 */
class EntitySchemaTest extends EntityUnitTestBase  {

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users_data'));
    $this->installSchema('system', array('router'));
    $this->database = $this->container->get('database');
  }

  /**
   * Tests the custom bundle field creation and deletion.
   */
  public function testCustomFieldCreateDelete() {
    // Install the module which adds the field.
    $this->installModule('entity_schema_test');
    $this->entityManager->clearCachedDefinitions();
    $storage_definitions = $this->entityManager->getFieldStorageDefinitions('entity_test');
    $this->assertNotNull($storage_definitions['custom_base_field'], 'Base field definition found.');
    $this->assertNotNull($storage_definitions['custom_bundle_field'], 'Bundle field definition found.');

    // Make sure the field schema can be created.
    $this->entityManager->onFieldStorageDefinitionCreate($storage_definitions['custom_base_field']);
    $this->entityManager->onFieldStorageDefinitionCreate($storage_definitions['custom_bundle_field']);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->entityManager->getStorage('entity_test')->getTableMapping();
    $base_table = current($table_mapping->getTableNames());
    $base_column = current($table_mapping->getColumnNames('custom_base_field'));
    $this->assertTrue($this->database->schema()->fieldExists($base_table, $base_column), 'Table column created');
    $table = $table_mapping->getDedicatedDataTableName($storage_definitions['custom_bundle_field']);
    $this->assertTrue($this->database->schema()->tableExists($table), 'Table created');

    // Make sure the field schema can be deleted.
    $this->entityManager->onFieldStorageDefinitionDelete($storage_definitions['custom_base_field']);
    $this->entityManager->onFieldStorageDefinitionDelete($storage_definitions['custom_bundle_field']);
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
    $entity_test_id = 'entity_test';
    $original = $this->entityManager->getDefinition($entity_test_id);
    $this->entityManager->clearCachedDefinitions();
    $this->state->set('entity_schema_update', $alter);
    $entity_type = $this->entityManager->getDefinition($entity_test_id);
    $this->entityManager->onEntityTypeUpdate($entity_type, $original);
  }

  /**
   * Tests that entity schema responds to changes in the entity type definition.
   */
  public function testEntitySchemaUpdate() {
    $this->installModule('entity_schema_test');
    $storage_definitions = $this->entityManager->getFieldStorageDefinitions('entity_test');
    $this->entityManager->onFieldStorageDefinitionCreate($storage_definitions['custom_base_field']);
    $this->entityManager->onFieldStorageDefinitionCreate($storage_definitions['custom_bundle_field']);
    $schema_handler = $this->database->schema();
    $tables = array('entity_test', 'entity_test_revision', 'entity_test_field_data', 'entity_test_field_revision');
    $dedicated_tables = array('entity_test__custom_bundle_field', 'entity_test_revision__custom_bundle_field');

    // Initially only the base table and the dedicated field data table should
    // exist.
    foreach ($tables as $index => $table) {
      $this->assertEqual($schema_handler->tableExists($table), !$index, SafeMarkup::format('Entity schema correct for the @table table.', array('@table' => $table)));
    }
    $this->assertTrue($schema_handler->tableExists($dedicated_tables[0]), SafeMarkup::format('Field schema correct for the @table table.', array('@table' => $table)));

    // Update the entity type definition and check that the entity schema now
    // supports translations and revisions.
    $this->updateEntityType(TRUE);
    foreach ($tables as $table) {
      $this->assertTrue($schema_handler->tableExists($table), SafeMarkup::format('Entity schema correct for the @table table.', array('@table' => $table)));
    }
    foreach ($dedicated_tables as $table) {
      $this->assertTrue($schema_handler->tableExists($table), SafeMarkup::format('Field schema correct for the @table table.', array('@table' => $table)));
    }

    // Revert changes and check that the entity schema now does not support
    // neither translations nor revisions.
    $this->updateEntityType(FALSE);
    foreach ($tables as $index => $table) {
      $this->assertEqual($schema_handler->tableExists($table), !$index, SafeMarkup::format('Entity schema correct for the @table table.', array('@table' => $table)));
    }
    $this->assertTrue($schema_handler->tableExists($dedicated_tables[0]), SafeMarkup::format('Field schema correct for the @table table.', array('@table' => $table)));
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
    foreach ($fields as $field_name) {
      $original_definition = $this->entityManager->getBaseFieldDefinitions('entity_test')[$field_name];
      $new_definition = clone $original_definition;
      $new_definition->setLabel($original_definition->getLabel() . ', the other one');
      $this->assertTrue($this->entityManager->getStorage('entity_test')
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
    $entities = \Drupal::entityManager()->getDefinitions();
    foreach ($entities as $entity_type_id => $definition) {
      if ($definition->getProvider() == 'entity_test') {
        $this->installEntitySchema($entity_type_id);
        $entity_type_ids[] = $entity_type_id;
      };
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
    $this->assertNotEqual($entity_type_id_count, 0, 'There are storage definitions provided by the entity_test module in the schema.');

    // Uninstall the entity_test module.
    $this->container->get('module_installer')->uninstall(array('entity_test'));

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
    $this->assertEqual($entity_type_id_count, 0, 'After uninstalling entity_test module the schema should not contains fields from entities provided by the module.');
  }

}
