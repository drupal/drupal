<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests Field SQL Storage .
 *
 * Field_sql_storage.module implements the default back-end storage plugin
 * for the Field Storage API.
 *
 * @group Entity
 */
class FieldSqlStorageTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'field_test', 'text', 'entity_test'];

  /**
   * The name of the created field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * @var int
   */
  protected $fieldCardinality;

  /**
   * A field storage to use in this class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Name of the data table of the field.
   *
   * @var string
   */
  protected $table;

  /**
   * Name of the revision table of the field.
   *
   * @var string
   */
  protected $revisionTable;

  /**
   * The table mapping for the tested entity type.
   *
   * @var \Drupal\Core\Entity\Sql\DefaultTableMapping
   */
  protected $tableMapping;

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $entity_type = 'entity_test_rev';

    $this->fieldName = strtolower($this->randomMachineName());
    $this->fieldCardinality = 4;
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $entity_type,
      'type' => 'test_field',
      'cardinality' => $this->fieldCardinality,
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => $entity_type,
    ]);
    $this->field->save();

    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = \Drupal::entityManager()->getStorage($entity_type)->getTableMapping();
    $this->tableMapping = $table_mapping;
    $this->table = $table_mapping->getDedicatedDataTableName($this->fieldStorage);
    $this->revisionTable = $table_mapping->getDedicatedRevisionTableName($this->fieldStorage);
  }

  /**
   * Tests field loading works correctly by inserting directly in the tables.
   */
  public function testFieldLoad() {
    $entity_type = $bundle = 'entity_test_rev';
    $storage = $this->container->get('entity.manager')->getStorage($entity_type);

    $columns = ['bundle', 'deleted', 'entity_id', 'revision_id', 'delta', 'langcode', $this->tableMapping->getFieldColumnName($this->fieldStorage, 'value')];

    // Create an entity with four revisions.
    $revision_ids = [];
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();
    $entity->save();
    $revision_ids[] = $entity->getRevisionId();
    for ($i = 0; $i < 4; $i++) {
      $entity->setNewRevision();
      $entity->save();
      $revision_ids[] = $entity->getRevisionId();
    }

    // Generate values and insert them directly in the storage tables.
    $values = [];
    $connection = Database::getConnection();
    $query = $connection->insert($this->revisionTable)->fields($columns);
    foreach ($revision_ids as $revision_id) {
      // Put one value too many.
      for ($delta = 0; $delta <= $this->fieldCardinality; $delta++) {
        $value = mt_rand(1, 127);
        $values[$revision_id][] = $value;
        $query->values([$bundle, 0, $entity->id(), $revision_id, $delta, $entity->language()->getId(), $value]);
      }
      $query->execute();
    }
    $query = $connection->insert($this->table)->fields($columns);
    foreach ($values[$revision_id] as $delta => $value) {
      $query->values([$bundle, 0, $entity->id(), $revision_id, $delta, $entity->language()->getId(), $value]);
    }
    $query->execute();

    // Load every revision and check the values.
    foreach ($revision_ids as $revision_id) {
      $entity = $storage->loadRevision($revision_id);
      foreach ($values[$revision_id] as $delta => $value) {
        if ($delta < $this->fieldCardinality) {
          $this->assertEqual($entity->{$this->fieldName}[$delta]->value, $value);
        }
        else {
          $this->assertFalse(array_key_exists($delta, $entity->{$this->fieldName}));
        }
      }
    }

    // Load the "current revision" and check the values.
    $entity = $storage->load($entity->id());
    foreach ($values[$revision_id] as $delta => $value) {
      if ($delta < $this->fieldCardinality) {
        $this->assertEqual($entity->{$this->fieldName}[$delta]->value, $value);
      }
      else {
        $this->assertFalse(array_key_exists($delta, $entity->{$this->fieldName}));
      }
    }

    // Add a translation in an unavailable language code and verify it is not
    // loaded.
    $unavailable_langcode = 'xx';
    $values = [$bundle, 0, $entity->id(), $entity->getRevisionId(), 0, $unavailable_langcode, mt_rand(1, 127)];
    $connection->insert($this->table)->fields($columns)->values($values)->execute();
    $connection->insert($this->revisionTable)->fields($columns)->values($values)->execute();
    $entity = $storage->load($entity->id());
    $this->assertFalse(array_key_exists($unavailable_langcode, $entity->{$this->fieldName}));
  }

  /**
   * Tests field saving works correctly by reading directly from the tables.
   */
  public function testFieldWrite() {
    $entity_type = $bundle = 'entity_test_rev';
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();

    $revision_values = [];

    // Check insert. Add one value too many.
    $values = [];
    for ($delta = 0; $delta <= $this->fieldCardinality; $delta++) {
      $values[$delta]['value'] = mt_rand(1, 127);
    }
    $entity->{$this->fieldName} = $values;
    $entity->save();

    $connection = Database::getConnection();
    // Read the tables and check the correct values have been stored.
    $rows = $connection->select($this->table, 't')->fields('t')->execute()->fetchAllAssoc('delta', \PDO::FETCH_ASSOC);
    $this->assertEqual(count($rows), $this->fieldCardinality);
    foreach ($rows as $delta => $row) {
      $expected = [
        'bundle' => $bundle,
        'deleted' => 0,
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'langcode' => $entity->language()->getId(),
        'delta' => $delta,
        $this->fieldName . '_value' => $values[$delta]['value'],
      ];
      $this->assertEqual($row, $expected, "Row $delta was stored as expected.");
    }

    // Test update. Add less values and check that the previous values did not
    // persist.
    $values = [];
    for ($delta = 0; $delta <= $this->fieldCardinality - 2; $delta++) {
      $values[$delta]['value'] = mt_rand(1, 127);
    }
    $entity->{$this->fieldName} = $values;
    $entity->save();
    $rows = $connection->select($this->table, 't')->fields('t')->execute()->fetchAllAssoc('delta', \PDO::FETCH_ASSOC);
    $this->assertEqual(count($rows), count($values));
    foreach ($rows as $delta => $row) {
      $expected = [
        'bundle' => $bundle,
        'deleted' => 0,
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'langcode' => $entity->language()->getId(),
        'delta' => $delta,
        $this->fieldName . '_value' => $values[$delta]['value'],
      ];
      $this->assertEqual($row, $expected, "Row $delta was stored as expected.");
    }

    // Create a new revision.
    $revision_values[$entity->getRevisionId()] = $values;
    $values = [];
    for ($delta = 0; $delta < $this->fieldCardinality; $delta++) {
      $values[$delta]['value'] = mt_rand(1, 127);
    }
    $entity->{$this->fieldName} = $values;
    $entity->setNewRevision();
    $entity->save();
    $revision_values[$entity->getRevisionId()] = $values;

    // Check that data for both revisions are in the revision table.
    foreach ($revision_values as $revision_id => $values) {
      $rows = $connection->select($this->revisionTable, 't')->fields('t')->condition('revision_id', $revision_id)->execute()->fetchAllAssoc('delta', \PDO::FETCH_ASSOC);
      $this->assertEqual(count($rows), min(count($values), $this->fieldCardinality));
      foreach ($rows as $delta => $row) {
        $expected = [
          'bundle' => $bundle,
          'deleted' => 0,
          'entity_id' => $entity->id(),
          'revision_id' => $revision_id,
          'langcode' => $entity->language()->getId(),
          'delta' => $delta,
          $this->fieldName . '_value' => $values[$delta]['value'],
        ];
        $this->assertEqual($row, $expected, "Row $delta was stored as expected.");
      }
    }

    // Test emptying the field.
    $entity->{$this->fieldName} = NULL;
    $entity->save();
    $rows = $connection->select($this->table, 't')->fields('t')->execute()->fetchAllAssoc('delta', \PDO::FETCH_ASSOC);
    $this->assertEqual(count($rows), 0);
  }

  /**
   * Tests that long entity type and field names do not break.
   */
  public function testLongNames() {
    // Use one of the longest entity_type names in core.
    $entity_type = $bundle = 'entity_test_label_callback';
    $this->installEntitySchema('entity_test_label_callback');
    $storage = $this->container->get('entity.manager')->getStorage($entity_type);

    // Create two fields and generate random values.
    $name_base = mb_strtolower($this->randomMachineName(FieldStorageConfig::NAME_MAX_LENGTH - 1));
    $field_names = [];
    $values = [];
    for ($i = 0; $i < 2; $i++) {
      $field_names[$i] = $name_base . $i;
      FieldStorageConfig::create([
        'field_name' => $field_names[$i],
        'entity_type' => $entity_type,
        'type' => 'test_field',
      ])->save();
      FieldConfig::create([
        'field_name' => $field_names[$i],
        'entity_type' => $entity_type,
        'bundle' => $bundle,
      ])->save();
      $values[$field_names[$i]] = mt_rand(1, 127);
    }

    // Save an entity with values.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create($values);
    $entity->save();

    // Load the entity back and check the values.
    $entity = $storage->load($entity->id());
    foreach ($field_names as $field_name) {
      $this->assertEqual($entity->get($field_name)->value, $values[$field_name]);
    }
  }

  /**
   * Test trying to update a field with data.
   */
  public function testUpdateFieldSchemaWithData() {
    $entity_type = 'entity_test_rev';
    // Create a decimal 5.2 field and add some data.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'decimal52',
      'entity_type' => $entity_type,
      'type' => 'decimal',
      'settings' => ['precision' => 5, 'scale' => 2],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $entity_type,
    ]);
    $field->save();
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'id' => 0,
        'revision_id' => 0,
      ]);
    $entity->decimal52->value = '1.235';
    $entity->save();

    // Attempt to update the field in a way that would work without data.
    $field_storage->setSetting('scale', 3);
    try {
      $field_storage->save();
      $this->fail(t('Cannot update field schema with data.'));
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass(t('Cannot update field schema with data.'));
    }
  }

  /**
   * Test that failure to create fields is handled gracefully.
   */
  public function testFieldUpdateFailure() {
    // Create a text field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_text',
      'entity_type' => 'entity_test_rev',
      'type' => 'text',
      'settings' => ['max_length' => 255],
    ]);
    $field_storage->save();

    // Attempt to update the field in a way that would break the storage. The
    // parenthesis suffix is needed because SQLite has *very* relaxed rules for
    // data types, so we actually need to provide an invalid SQL syntax in order
    // to break it.
    // @see https://www.sqlite.org/datatype3.html
    $prior_field_storage = $field_storage;
    $field_storage->setSetting('max_length', '-1)');
    try {
      $field_storage->save();
      $this->fail(t('Update succeeded.'));
    }
    catch (\Exception $e) {
      $this->pass(t('Update properly failed.'));
    }

    // Ensure that the field tables are still there.
    $tables = [
      $this->tableMapping->getDedicatedDataTableName($prior_field_storage),
      $this->tableMapping->getDedicatedRevisionTableName($prior_field_storage),
    ];
    $schema = Database::getConnection()->schema();
    foreach ($tables as $table_name) {
      $this->assertTrue($schema->tableExists($table_name), t('Table %table exists.', ['%table' => $table_name]));
    }
  }

  /**
   * Test adding and removing indexes while data is present.
   */
  public function testFieldUpdateIndexesWithData() {
    // Create a decimal field.
    $field_name = 'testfield';
    $entity_type = 'entity_test_rev';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'text',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $entity_type,
    ]);
    $field->save();
    $tables = [$this->tableMapping->getDedicatedDataTableName($field_storage), $this->tableMapping->getDedicatedRevisionTableName($field_storage)];

    // Verify the indexes we will create do not exist yet.
    foreach ($tables as $table) {
      $this->assertFalse(Database::getConnection()->schema()->indexExists($table, 'value'), t("No index named value exists in @table", ['@table' => $table]));
      $this->assertFalse(Database::getConnection()->schema()->indexExists($table, 'value_format'), t("No index named value_format exists in @table", ['@table' => $table]));
    }

    // Add data so the table cannot be dropped.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'id' => 1,
        'revision_id' => 1,
      ]);
    $entity->$field_name->value = 'field data';
    $entity->enforceIsNew();
    $entity->save();

    // Add an index.
    $field_storage->setIndexes(['value' => [['value', 255]]]);
    $field_storage->save();
    foreach ($tables as $table) {
      $this->assertTrue(Database::getConnection()->schema()->indexExists($table, "{$field_name}_value"), t("Index on value created in @table", ['@table' => $table]));
    }

    // Add a different index, removing the existing custom one.
    $field_storage->setIndexes(['value_format' => [['value', 127], ['format', 127]]]);
    $field_storage->save();
    foreach ($tables as $table) {
      $this->assertTrue(Database::getConnection()->schema()->indexExists($table, "{$field_name}_value_format"), t("Index on value_format created in @table", ['@table' => $table]));
      $this->assertFalse(Database::getConnection()->schema()->indexExists($table, "{$field_name}_value"), t("Index on value removed in @table", ['@table' => $table]));
    }

    // Verify that the tables were not dropped in the process.
    $entity = $this->container->get('entity.manager')->getStorage($entity_type)->load(1);
    $this->assertEqual($entity->$field_name->value, 'field data', t("Index changes performed without dropping the tables"));
  }

  /**
   * Test foreign key support.
   */
  public function testFieldSqlStorageForeignKeys() {
    // Create a 'shape' field, with a configurable foreign key (see
    // field_test_field_schema()).
    $field_name = 'testfield';
    $foreign_key_name = 'shape';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'shape',
      'settings' => ['foreign_key_name' => $foreign_key_name],
    ]);
    $field_storage->save();
    // Get the field schema.
    $schema = $field_storage->getSchema();

    // Retrieve the field definition and check that the foreign key is in place.
    $this->assertEqual($schema['foreign keys'][$foreign_key_name]['table'], $foreign_key_name, 'Foreign key table name preserved through CRUD');
    $this->assertEqual($schema['foreign keys'][$foreign_key_name]['columns'][$foreign_key_name], 'id', 'Foreign key column name preserved through CRUD');

    // Update the field settings, it should update the foreign key definition too.
    $foreign_key_name = 'color';
    $field_storage->setSetting('foreign_key_name', $foreign_key_name);
    $field_storage->save();
    // Reload the field schema after the update.
    $schema = $field_storage->getSchema();

    // Check that the foreign key is in place.
    $this->assertEqual($schema['foreign keys'][$foreign_key_name]['table'], $foreign_key_name, 'Foreign key table name modified after update');
    $this->assertEqual($schema['foreign keys'][$foreign_key_name]['columns'][$foreign_key_name], 'id', 'Foreign key column name modified after update');
  }

  /**
   * Tests table name generation.
   */
  public function testTableNames() {
    // Note: we need to test entity types with long names. We therefore use
    // fields on imaginary entity types (works as long as we don't actually save
    // them), and just check the generated table names.

    // Short entity type and field name.
    $entity_type = 'short_entity_type';
    $field_name = 'short_field_name';
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => 'test_field',
    ]);
    $expected = 'short_entity_type__short_field_name';
    $this->assertEqual($this->tableMapping->getDedicatedDataTableName($field_storage), $expected);
    $expected = 'short_entity_type_revision__short_field_name';
    $this->assertEqual($this->tableMapping->getDedicatedRevisionTableName($field_storage), $expected);

    // Short entity type, long field name
    $entity_type = 'short_entity_type';
    $field_name = 'long_field_name_abcdefghijklmnopqrstuvwxyz';
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => 'test_field',
    ]);
    $expected = 'short_entity_type__' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedDataTableName($field_storage), $expected);
    $expected = 'short_entity_type_r__' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedRevisionTableName($field_storage), $expected);

    // Long entity type, short field name
    $entity_type = 'long_entity_type_abcdefghijklmnopqrstuvwxyz';
    $field_name = 'short_field_name';
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => 'test_field',
    ]);
    $expected = 'long_entity_type_abcdefghijklmno__' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedDataTableName($field_storage), $expected);
    $expected = 'long_entity_type_abcdefghijklmno_r__' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedRevisionTableName($field_storage), $expected);

    // Long entity type and field name.
    $entity_type = 'long_entity_type_abcdefghijklmnopqrstuvwxyz';
    $field_name = 'long_field_name_abcdefghijklmnopqrstuvwxyz';
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => 'test_field',
    ]);
    $expected = 'long_entity_type_abcdefghijklmno__' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedDataTableName($field_storage), $expected);
    $expected = 'long_entity_type_abcdefghijklmno_r__' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedRevisionTableName($field_storage), $expected);
    // Try creating a second field and check there are no clashes.
    $field_storage2 = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name . '2',
      'type' => 'test_field',
    ]);
    $this->assertNotEqual($this->tableMapping->getDedicatedDataTableName($field_storage), $this->tableMapping->getDedicatedDataTableName($field_storage2));
    $this->assertNotEqual($this->tableMapping->getDedicatedRevisionTableName($field_storage), $this->tableMapping->getDedicatedRevisionTableName($field_storage2));

    // Deleted field.
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'some_entity_type',
      'field_name' => 'some_field_name',
      'type' => 'test_field',
      'deleted' => TRUE,
    ]);
    $expected = 'field_deleted_data_' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedDataTableName($field_storage, TRUE), $expected);
    $expected = 'field_deleted_revision_' . substr(hash('sha256', $field_storage->uuid()), 0, 10);
    $this->assertEqual($this->tableMapping->getDedicatedRevisionTableName($field_storage, TRUE), $expected);
  }

}
