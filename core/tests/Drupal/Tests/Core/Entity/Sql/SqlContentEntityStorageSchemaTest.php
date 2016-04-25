<?php

namespace Drupal\Tests\Core\Entity\Sql;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema
 * @group Entity
 */
class SqlContentEntityStorageSchemaTest extends UnitTestCase {

  /**
   * The mocked DB schema handler.
   *
   * @var \Drupal\Core\Database\Schema|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dbSchemaHandler;

  /**
   * The mocked entity manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked entity type used in this test.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $entityType;

  /**
   * The mocked SQL storage used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * The mocked field definitions used in this test.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
   */
  protected $storageDefinitions;

  /**
   * The storage schema handler used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema|\PHPUnit_Framework_MockObject_MockObject.
   */
  protected $storageSchema;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storage->expects($this->any())
      ->method('getBaseTable')
      ->will($this->returnValue('entity_test'));

    // Add an ID field. This also acts as a test for a simple, single-column
    // field.
    $this->setUpStorageDefinition('id', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
        ),
      ),
    ));
  }

  /**
   * Tests the schema for non-revisionable, non-translatable entities.
   *
   * @covers ::__construct
   * @covers ::getEntitySchemaTables
   * @covers ::initializeBaseTable
   * @covers ::addTableDefaults
   * @covers ::getEntityIndexName
   * @covers ::getFieldIndexes
   * @covers ::getFieldUniqueKeys
   * @covers ::getFieldForeignKeys
   * @covers ::getFieldSchemaData
   * @covers ::processBaseTable
   * @covers ::processIdentifierSchema
   */
  public function testGetSchemaBase() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    // Add a field with a 'length' constraint.
    $this->setUpStorageDefinition('name', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
    ));
    // Add a multi-column field.
    $this->setUpStorageDefinition('description', array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
        ),
        'format' => array(
          'type' => 'varchar',
        ),
      ),
    ));
    // Add a field with a unique key.
    $this->setUpStorageDefinition('uuid', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 128,
        ),
      ),
      'unique keys' => array(
        'value' => array('value'),
      ),
    ));
    // Add a field with a unique key, specified as column name and length.
    $this->setUpStorageDefinition('hash', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 20,
        ),
      ),
      'unique keys' => array(
        'value' => array(array('value', 10)),
      ),
    ));
    // Add a field with a multi-column unique key.
    $this->setUpStorageDefinition('email', array(
      'columns' => array(
        'username' => array(
          'type' => 'varchar',
        ),
        'hostname' => array(
          'type' => 'varchar',
        ),
        'domain' => array(
          'type' => 'varchar',
        )
      ),
      'unique keys' => array(
        'email' => array('username', 'hostname', array('domain', 3)),
      ),
    ));
    // Add a field with an index.
    $this->setUpStorageDefinition('owner', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
      ),
      'indexes' => array(
        'target_id' => array('target_id'),
      ),
    ));
    // Add a field with an index, specified as column name and length.
    $this->setUpStorageDefinition('translator', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
      ),
      'indexes' => array(
        'target_id' => array(array('target_id', 10)),
      ),
    ));
    // Add a field with a multi-column index.
    $this->setUpStorageDefinition('location', array(
      'columns' => array(
        'country' => array(
          'type' => 'varchar',
        ),
        'state' => array(
          'type' => 'varchar',
        ),
        'city' => array(
          'type' => 'varchar',
        )
      ),
      'indexes' => array(
        'country_state_city' => array('country', 'state', array('city', 10)),
      ),
    ));
    // Add a field with a foreign key.
    $this->setUpStorageDefinition('editor', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
      ),
      'foreign keys' => array(
        'user_id' => array(
          'table' => 'users',
          'columns' => array('target_id' => 'uid'),
        ),
      ),
    ));
    // Add a multi-column field with a foreign key.
    $this->setUpStorageDefinition('editor_revision', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
        'target_revision_id' => array(
          'type' => 'int',
        ),
      ),
      'foreign keys' => array(
        'user_id' => array(
          'table' => 'users',
          'columns' => array('target_id' => 'uid'),
        ),
      ),
    ));
    // Add a field with a really long index.
    $this->setUpStorageDefinition('long_index_name', array(
      'columns' => array(
        'long_index_name' => array(
          'type' => 'int',
        ),
      ),
      'indexes' => array(
        'long_index_name_really_long_long_name' => array(array('long_index_name', 10)),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'name' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => FALSE,
          ),
          'description__value' => array(
            'type' => 'text',
            'not null' => FALSE,
          ),
          'description__format' => array(
            'type' => 'varchar',
            'not null' => FALSE,
          ),
          'uuid' => array(
            'type' => 'varchar',
            'length' => 128,
            'not null' => FALSE,
          ),
          'hash' => array(
            'type' => 'varchar',
            'length' => 20,
            'not null' => FALSE,
          ),
          'email__username' => array(
            'type' => 'varchar',
            'not null' => FALSE,
          ),
          'email__hostname' => array(
            'type' => 'varchar',
            'not null' => FALSE,
          ),
          'email__domain' => array(
            'type' => 'varchar',
            'not null' => FALSE,
          ),
          'owner' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
          'translator' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
          'location__country' => array(
            'type' => 'varchar',
            'not null' => FALSE,
          ),
          'location__state' => array(
            'type' => 'varchar',
            'not null' => FALSE,
          ),
          'location__city' => array(
            'type' => 'varchar',
            'not null' => FALSE,
          ),
          'editor' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
          'editor_revision__target_id' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
          'editor_revision__target_revision_id' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
          'long_index_name' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
        ),
        'primary key' => array('id'),
        'unique keys' => array(
          'entity_test_field__uuid__value' => array('uuid'),
          'entity_test_field__hash__value' => array(array('hash', 10)),
          'entity_test_field__email__email' => array(
            'email__username',
            'email__hostname',
            array('email__domain', 3),
          ),
        ),
        'indexes' => array(
          'entity_test_field__owner__target_id' => array('owner'),
          'entity_test_field__translator__target_id' => array(
            array('translator', 10),
          ),
          'entity_test_field__location__country_state_city' => array(
            'location__country',
            'location__state',
            array('location__city', 10),
          ),
          'entity_test__b588603cb9' => array(
            array('long_index_name', 10),
          ),

        ),
        'foreign keys' => array(
          'entity_test_field__editor__user_id' => array(
            'table' => 'users',
            'columns' => array('editor' => 'uid'),
          ),
          'entity_test_field__editor_revision__user_id' => array(
            'table' => 'users',
            'columns' => array('editor_revision__target_id' => 'uid'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns('entity_test', array('default_langcode'));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->assertNull(
      $this->storageSchema->onEntityTypeCreate($this->entityType)
    );
  }

  /**
   * Tests the schema for revisionable, non-translatable entities.
   *
   * @covers ::__construct
   * @covers ::getEntitySchemaTables
   * @covers ::initializeBaseTable
   * @covers ::initializeRevisionTable
   * @covers ::addTableDefaults
   * @covers ::getEntityIndexName
   * @covers ::processRevisionTable
   * @covers ::processIdentifierSchema
   */
  public function testGetSchemaRevisionable() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array(
        'id' => 'id',
        'revision' => 'revision_id',
      ),
    ));

    $this->storage->expects($this->exactly(2))
      ->method('getRevisionTable')
      ->will($this->returnValue('entity_test_revision'));

    $this->setUpStorageDefinition('revision_id', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
        ),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'type' => 'int',
            'not null' => FALSE,
          )
        ),
        'primary key' => array('id'),
        'unique keys' => array(
          'entity_test__revision_id' => array('revision_id'),
        ),
        'indexes' => array(),
        'foreign keys' => array(
          'entity_test__revision' => array(
            'table' => 'entity_test_revision',
            'columns' => array('revision_id' => 'revision_id'),
          )
        ),
      ),
      'entity_test_revision' => array(
        'description' => 'The revision table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'type' => 'serial',
            'not null' => TRUE,
          ),
        ),
        'primary key' => array('revision_id'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__id' => array('id'),
        ),
        'foreign keys' => array(
          'entity_test__revisioned' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_revision', array_keys($this->storageDefinitions));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests the schema for non-revisionable, translatable entities.
   *
   * @covers ::__construct
   * @covers ::getEntitySchemaTables
   * @covers ::initializeDataTable
   * @covers ::addTableDefaults
   * @covers ::getEntityIndexName
   * @covers ::processDataTable
   */
  public function testGetSchemaTranslatable() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array(
        'id' => 'id',
        'langcode' => 'langcode',
      ),
    ));

    $this->storage->expects($this->any())
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));

    $this->setUpStorageDefinition('langcode', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
        ),
      ),
    ));

    $this->setUpStorageDefinition('default_langcode', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'tiny',
        ),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'langcode' => array(
            'type' => 'varchar',
            'not null' => TRUE,
          )
        ),
        'primary key' => array('id'),
        'unique keys' => array(),
        'indexes' => array(),
        'foreign keys' => array(),
      ),
      'entity_test_field_data' => array(
        'description' => 'The data table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'int',
            'not null' => TRUE,
          ),
          'langcode' => array(
            'type' => 'varchar',
            'not null' => TRUE,
          ),
          'default_langcode' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => true,
          ),
        ),
        'primary key' => array('id', 'langcode'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__id__default_langcode__langcode' => array(
            0 => 'id',
            1 => 'default_langcode',
            2 => 'langcode',
          ),
        ),
        'foreign keys' => array(
          'entity_test' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $non_data_fields = array_keys($this->storageDefinitions);
    unset($non_data_fields[array_search('default_langcode', $non_data_fields)]);
    $table_mapping->setFieldNames('entity_test', $non_data_fields);
    $table_mapping->setFieldNames('entity_test_field_data', array_keys($this->storageDefinitions));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->assertNull(
      $this->storageSchema->onEntityTypeCreate($this->entityType)
    );
  }

  /**
   * Tests the schema for revisionable, translatable entities.
   *
   * @covers ::__construct
   * @covers ::getEntitySchemaTables
   * @covers ::initializeDataTable
   * @covers ::addTableDefaults
   * @covers ::getEntityIndexName
   * @covers ::initializeRevisionDataTable
   * @covers ::processRevisionDataTable
   */
  public function testGetSchemaRevisionableTranslatable() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array(
        'id' => 'id',
        'revision' => 'revision_id',
        'langcode' => 'langcode',
      ),
    ));

    $this->storage->expects($this->exactly(3))
      ->method('getRevisionTable')
      ->will($this->returnValue('entity_test_revision'));
    $this->storage->expects($this->once())
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->storage->expects($this->once())
      ->method('getRevisionDataTable')
      ->will($this->returnValue('entity_test_revision_field_data'));

    $this->setUpStorageDefinition('revision_id', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
        ),
      ),
    ));
    $this->setUpStorageDefinition('langcode', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
        ),
      ),
    ));
    $this->setUpStorageDefinition('default_langcode', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'tiny',
        ),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
          'langcode' => array(
            'type' => 'varchar',
            'not null' => TRUE,
          )
        ),
        'primary key' => array('id'),
        'unique keys' => array(
          'entity_test__revision_id' => array('revision_id'),
        ),
        'indexes' => array(),
        'foreign keys' => array(
          'entity_test__revision' => array(
            'table' => 'entity_test_revision',
            'columns' => array('revision_id' => 'revision_id'),
          ),
        ),
      ),
      'entity_test_revision' => array(
        'description' => 'The revision table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'langcode' => array(
            'type' => 'varchar',
            'not null' => TRUE,
          ),
        ),
        'primary key' => array('revision_id'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__id' => array('id'),
        ),
        'foreign keys' => array(
          'entity_test__revisioned' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
      'entity_test_field_data' => array(
        'description' => 'The data table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'type' => 'int',
            'not null' => TRUE,
          ),
          'langcode' => array(
            'type' => 'varchar',
            'not null' => TRUE,
          ),
          'default_langcode' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => true,
          ),
        ),
        'primary key' => array('id', 'langcode'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__revision_id' => array('revision_id'),
          'entity_test__id__default_langcode__langcode' => array(
            0 => 'id',
            1 => 'default_langcode',
            2 => 'langcode',
          ),
        ),
        'foreign keys' => array(
          'entity_test' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
      'entity_test_revision_field_data' => array(
        'description' => 'The revision data table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'type' => 'int',
            'not null' => TRUE,
          ),
          'langcode' => array(
            'type' => 'varchar',
            'not null' => TRUE,
          ),
          'default_langcode' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => true,
          ),
        ),
        'primary key' => array('revision_id', 'langcode'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__id__default_langcode__langcode' => array(
            0 => 'id',
            1 => 'default_langcode',
            2 => 'langcode',
          ),
        ),
        'foreign keys' => array(
          'entity_test' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
          'entity_test__revision' => array(
            'table' => 'entity_test_revision',
            'columns' => array('revision_id' => 'revision_id'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $non_data_fields = array_keys($this->storageDefinitions);
    unset($non_data_fields[array_search('default_langcode', $non_data_fields)]);
    $table_mapping->setFieldNames('entity_test', $non_data_fields);
    $table_mapping->setFieldNames('entity_test_revision', $non_data_fields);
    $table_mapping->setFieldNames('entity_test_field_data', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_revision_field_data', array_keys($this->storageDefinitions));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests the schema for a field dedicated table.
   *
   * @covers ::onFieldStorageDefinitionCreate
   * @covers ::getDedicatedTableSchema
   * @covers ::createDedicatedTableSchema
   */
  public function testDedicatedTableSchema() {
    $entity_type_id = 'entity_test';
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    // Setup a field having a dedicated schema.
    $field_name = $this->getRandomGenerator()->name();
    $this->setUpStorageDefinition($field_name, array(
      'columns' => array(
        'shape' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
        'color' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
      ),
      'foreign keys' => array(
        'color' => array(
          'table' => 'color',
          'columns' => array(
            'color' => 'id'
          ),
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(),
    ));

    $field_storage = $this->storageDefinitions[$field_name];
    $field_storage
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('shape'));
    $field_storage
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->will($this->returnValue($entity_type_id));
    $field_storage
      ->expects($this->any())
      ->method('isMultiple')
      ->will($this->returnValue(TRUE));

    $this->storageDefinitions['id']
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('integer'));

    $expected = array(
      $entity_type_id . '__' . $field_name => array(
        'description' => "Data storage for $entity_type_id field $field_name.",
        'fields' => array(
          'bundle' => array(
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => true,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ),
          'deleted' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => true,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ),
          'entity_id' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The entity id this data is attached to',
          ),
          'revision_id' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id',
          ),
          'langcode' => array(
            'type' => 'varchar_ascii',
            'length' => 32,
            'not null' => true,
            'default' => '',
            'description' => 'The language code for this data item.',
          ),
          'delta' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ),
          $field_name . '_shape' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
          $field_name . '_color' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
        ),
        'primary key' => array('entity_id', 'deleted', 'delta', 'langcode'),
        'indexes' => array(
          'bundle' => array('bundle'),
          'revision_id' => array('revision_id'),
        ),
        'foreign keys' => array(
          $field_name . '_color' => array(
            'table' => 'color',
            'columns' => array(
              $field_name . '_color' => 'id',
            ),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames($entity_type_id, array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns($entity_type_id, array('default_langcode'));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->assertNull(
      $this->storageSchema->onFieldStorageDefinitionCreate($field_storage)
    );
  }

  /**
   * Tests the schema for a field dedicated table for an entity with a string identifier.
   *
   * @covers ::onFieldStorageDefinitionCreate
   * @covers ::getDedicatedTableSchema
   * @covers ::createDedicatedTableSchema
   */
  public function testDedicatedTableSchemaForEntityWithStringIdentifier() {
    $entity_type_id = 'entity_test';
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    // Setup a field having a dedicated schema.
    $field_name = $this->getRandomGenerator()->name();
    $this->setUpStorageDefinition($field_name, array(
      'columns' => array(
        'shape' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
        'color' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
      ),
      'foreign keys' => array(
        'color' => array(
          'table' => 'color',
          'columns' => array(
            'color' => 'id'
          ),
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(),
    ));

    $field_storage = $this->storageDefinitions[$field_name];
    $field_storage
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('shape'));
    $field_storage
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->will($this->returnValue($entity_type_id));
    $field_storage
      ->expects($this->any())
      ->method('isMultiple')
      ->will($this->returnValue(TRUE));

    $this->storageDefinitions['id']
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('string'));

    $expected = array(
      $entity_type_id . '__' . $field_name => array(
        'description' => "Data storage for $entity_type_id field $field_name.",
        'fields' => array(
          'bundle' => array(
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => true,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ),
          'deleted' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => true,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ),
          'entity_id' => array(
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => true,
            'description' => 'The entity id this data is attached to',
          ),
          'revision_id' => array(
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => true,
            'description' => 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id',
          ),
          'langcode' => array(
            'type' => 'varchar_ascii',
            'length' => 32,
            'not null' => true,
            'default' => '',
            'description' => 'The language code for this data item.',
          ),
          'delta' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ),
          $field_name . '_shape' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
          $field_name . '_color' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
        ),
        'primary key' => array('entity_id', 'deleted', 'delta', 'langcode'),
        'indexes' => array(
          'bundle' => array('bundle'),
          'revision_id' => array('revision_id'),
        ),
        'foreign keys' => array(
          $field_name . '_color' => array(
            'table' => 'color',
            'columns' => array(
              $field_name . '_color' => 'id',
            ),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames($entity_type_id, array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns($entity_type_id, array('default_langcode'));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->assertNull(
      $this->storageSchema->onFieldStorageDefinitionCreate($field_storage)
    );
  }

  public function providerTestRequiresEntityDataMigration() {
    $updated_entity_type_definition = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $updated_entity_type_definition->expects($this->any())
      ->method('getStorageClass')
      // A class that exists, *any* class.
      ->willReturn('\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema');
    $original_entity_type_definition = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $original_entity_type_definition->expects($this->any())
      ->method('getStorageClass')
      // A class that exists, *any* class.
      ->willReturn('\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema');
    $original_entity_type_definition_other_nonexisting = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $original_entity_type_definition_other_nonexisting->expects($this->any())
      ->method('getStorageClass')
      ->willReturn('bar');
    $original_entity_type_definition_other_existing = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $original_entity_type_definition_other_existing->expects($this->any())
      ->method('getStorageClass')
      // A class that exists, *any* class.
      ->willReturn('\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema');

    return [
      // Case 1: same storage class, ::hasData() === TRUE.
      [$updated_entity_type_definition, $original_entity_type_definition, TRUE, TRUE, TRUE],
      // Case 2: same storage class, ::hasData() === FALSE.
      [$updated_entity_type_definition, $original_entity_type_definition, FALSE, TRUE, FALSE],
      // Case 3: different storage class, original storage class does not exist.
      [$updated_entity_type_definition, $original_entity_type_definition_other_nonexisting, NULL, TRUE, TRUE],
      // Case 4: different storage class, original storage class exists,
      // ::hasData() === TRUE.
      [$updated_entity_type_definition, $original_entity_type_definition_other_existing, TRUE, TRUE, TRUE],
      // Case 5: different storage class, original storage class exists,
      // ::hasData() === FALSE.
      [$updated_entity_type_definition, $original_entity_type_definition_other_existing, FALSE, TRUE, FALSE],
      // Case 6: same storage class, ::hasData() === TRUE, no structure changes.
      [$updated_entity_type_definition, $original_entity_type_definition, TRUE, FALSE, FALSE],
      // Case 7: different storage class, original storage class exists,
      //::hasData() === TRUE, no structure changes.
      [$updated_entity_type_definition, $original_entity_type_definition_other_existing, TRUE, FALSE, FALSE],
    ];
  }

  /**
   * @covers ::requiresEntityDataMigration
   *
   * @dataProvider providerTestRequiresEntityDataMigration
   */
  public function testRequiresEntityDataMigration($updated_entity_type_definition, $original_entity_type_definition, $original_storage_has_data, $shared_table_structure_changed, $migration_required) {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    $original_storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $original_storage->expects($this->exactly(is_null($original_storage_has_data) || !$shared_table_structure_changed ? 0 : 1))
      ->method('hasData')
      ->willReturn($original_storage_has_data);

    // Assert hasData() is never called on the new storage definition.
    $this->storage->expects($this->never())
      ->method('hasData');

    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityManager->expects($this->any())
      ->method('createHandlerInstance')
      ->willReturn($original_storage);

    $this->storageSchema = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema')
      ->setConstructorArgs(array($this->entityManager, $this->entityType, $this->storage, $connection))
      ->setMethods(array('installedStorageSchema', 'hasSharedTableStructureChange'))
      ->getMock();

    $this->storageSchema->expects($this->any())
      ->method('hasSharedTableStructureChange')
      ->with($updated_entity_type_definition, $original_entity_type_definition)
      ->willReturn($shared_table_structure_changed);

    $this->assertEquals($migration_required, $this->storageSchema->requiresEntityDataMigration($updated_entity_type_definition, $original_entity_type_definition));
  }

  /**
   * Data provider for ::testRequiresEntityStorageSchemaChanges().
   */
  public function providerTestRequiresEntityStorageSchemaChanges() {

    $cases = [];

    $updated_entity_type_definition = $this->getMock('\Drupal\Core\Entity\ContentEntityTypeInterface');
    $original_entity_type_definition = $this->getMock('\Drupal\Core\Entity\ContentEntityTypeInterface');

    $updated_entity_type_definition->expects($this->any())
      ->method('id')
      ->willReturn('entity_test');
    $updated_entity_type_definition->expects($this->any())
      ->method('getKey')
      ->willReturn('id');
    $original_entity_type_definition->expects($this->any())
      ->method('id')
      ->willReturn('entity_test');
    $original_entity_type_definition->expects($this->any())
      ->method('getKey')
      ->willReturn('id');

    // Storage class changes should not impact this at all, and should not be
    // checked.
    $updated = clone $updated_entity_type_definition;
    $original = clone $original_entity_type_definition;
    $updated->expects($this->never())
      ->method('getStorageClass');
    $original->expects($this->never())
      ->method('getStorageClass');

    // Case 1: No shared table changes should not require change.
    $cases[] = [$updated, $original, FALSE, FALSE, FALSE];

    // Case 2: A change in the entity schema should result in required changes.
    $cases[] = [$updated, $original, TRUE, TRUE, FALSE];

    // Case 3: Has shared table changes should result in required changes.
    $cases[] = [$updated, $original, TRUE, FALSE, TRUE];

    // Case 4: Changing translation should result in required changes.
    $updated = clone $updated_entity_type_definition;
    $updated->expects($this->once())
      ->method('isTranslatable')
      ->willReturn(FALSE);
    $original = clone $original_entity_type_definition;
    $original->expects($this->once())
      ->method('isTranslatable')
      ->willReturn(TRUE);
    $cases[] = [$updated, $original, TRUE, FALSE, FALSE];

    // Case 5: Changing revisionable should result in required changes.
    $updated = clone $updated_entity_type_definition;
    $updated->expects($this->once())
      ->method('isRevisionable')
      ->willReturn(FALSE);
    $original = clone $original_entity_type_definition;
    $original->expects($this->once())
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $cases[] = [$updated, $original, TRUE, FALSE, FALSE];

    return $cases;
  }

  /**
   * @covers ::requiresEntityStorageSchemaChanges
   *
   * @dataProvider providerTestRequiresEntityStorageSchemaChanges
   */
  public function testRequiresEntityStorageSchemaChanges(ContentEntityTypeInterface $updated, ContentEntityTypeInterface $original, $requires_change, $change_schema, $change_shared_table) {

    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    $this->setUpStorageSchema();
    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns('entity_test', array('default_langcode'));
    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    // Setup storage schema.
    if ($change_schema) {
      $this->storageSchema->expects($this->once())
        ->method('loadEntitySchemaData')
        ->willReturn(array());
    }
    else {
      $expected = [
        'entity_test' => [
          'primary key' => ['id'],
        ],
      ];
      $this->storageSchema->expects($this->any())
        ->method('loadEntitySchemaData')
        ->willReturn($expected);
    }

    if ($change_shared_table) {
      $this->storageSchema->expects($this->once())
        ->method('hasSharedTableNameChanges')
        ->willReturn(TRUE);
    }

    $this->assertEquals($requires_change, $this->storageSchema->requiresEntityStorageSchemaChanges($updated, $original));
  }

  /**
   * Sets up the storage schema object to test.
   *
   * This uses the field definitions set in $this->storageDefinitions.
   *
   * @param array $expected
   *   (optional) An associative array describing the expected entity schema to
   *   be created. Defaults to expecting nothing.
   */
  protected function setUpStorageSchema(array $expected = array()) {
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityType->id())
      ->will($this->returnValue($this->entityType));

    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with($this->entityType->id())
      ->will($this->returnValue($this->storageDefinitions));

    $this->dbSchemaHandler = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();

    if ($expected) {
      $invocation_count = 0;
      $expected_table_names = array_keys($expected);
      $expected_table_schemas = array_values($expected);

      $this->dbSchemaHandler->expects($this->any())
        ->method('createTable')
        ->with(
          $this->callback(function($table_name) use (&$invocation_count, $expected_table_names) {
            return $expected_table_names[$invocation_count] == $table_name;
          }),
          $this->callback(function($table_schema) use (&$invocation_count, $expected_table_schemas) {
            return $expected_table_schemas[$invocation_count] == $table_schema;
          })
        )
        ->will($this->returnCallback(function() use (&$invocation_count) {
          $invocation_count++;
        }));
    }

    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $connection->expects($this->any())
      ->method('schema')
      ->will($this->returnValue($this->dbSchemaHandler));

    $key_value = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreInterface');
    $this->storageSchema = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema')
      ->setConstructorArgs(array($this->entityManager, $this->entityType, $this->storage, $connection))
      ->setMethods(array('installedStorageSchema', 'loadEntitySchemaData', 'hasSharedTableNameChanges', 'isTableEmpty'))
      ->getMock();
    $this->storageSchema
      ->expects($this->any())
      ->method('installedStorageSchema')
      ->will($this->returnValue($key_value));
    $this->storageSchema
      ->expects($this->any())
      ->method('isTableEmpty')
      ->willReturn(FALSE);
  }

  /**
   * Sets up a field definition.
   *
   * @param string $field_name
   *   The field name.
   * @param array $schema
   *   The schema array of the field definition, as returned from
   *   FieldStorageDefinitionInterface::getSchema().
   */
  public function setUpStorageDefinition($field_name, array $schema) {
    $this->storageDefinitions[$field_name] = $this->getMock('Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface');
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('isBaseField')
      ->will($this->returnValue(TRUE));
    // getName() is called once for each table.
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($field_name));
    // getSchema() is called once for each table.
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getSchema')
      ->will($this->returnValue($schema));
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getColumns')
      ->will($this->returnValue($schema['columns']));
    // Add property definitions.
    if (!empty($schema['columns'])) {
      $property_definitions = array();
      foreach ($schema['columns'] as $column => $info) {
        $property_definitions[$column] = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
        $property_definitions[$column]->expects($this->any())
          ->method('isRequired')
          ->will($this->returnValue(!empty($info['not null'])));
      }
      $this->storageDefinitions[$field_name]->expects($this->any())
        ->method('getPropertyDefinitions')
        ->will($this->returnValue($property_definitions));
    }
  }

  /**
   * ::onEntityTypeUpdate
   */
  public function testonEntityTypeUpdateWithNewIndex() {
    $this->entityType = $original_entity_type = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    // Add a field with a really long index.
    $this->setUpStorageDefinition('long_index_name', array(
      'columns' => array(
        'long_index_name' => array(
          'type' => 'int',
        ),
      ),
      'indexes' => array(
        'long_index_name_really_long_long_name' => array(array('long_index_name', 10)),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'long_index_name' => array(
            'type' => 'int',
            'not null' => FALSE,
          ),
        ),
        'indexes' => array(
          'entity_test__b588603cb9' => array(
            array('long_index_name', 10),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns('entity_test', array('default_langcode'));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->expects($this->any())
      ->method('loadEntitySchemaData')
      ->willReturn([
        'entity_test' => [
          'indexes' => [
            // A changed index definition.
            'entity_test__b588603cb9' => ['longer_index_name'],
            // An index that has been removed.
            'entity_test__removed_field' => ['removed_field'],
          ],
        ],
      ]);

    // The original indexes should be dropped before the new one is added.
    $this->dbSchemaHandler->expects($this->at(0))
      ->method('dropIndex')
      ->with('entity_test', 'entity_test__b588603cb9');
    $this->dbSchemaHandler->expects($this->at(1))
      ->method('dropIndex')
      ->with('entity_test', 'entity_test__removed_field');

    $this->dbSchemaHandler->expects($this->atLeastOnce())
      ->method('fieldExists')
      ->willReturn(TRUE);
    $this->dbSchemaHandler->expects($this->atLeastOnce())
      ->method('addIndex')
      ->with('entity_test', 'entity_test__b588603cb9', [['long_index_name', 10]], $this->callback(function($actual_value) use ($expected)  {
        $this->assertEquals($expected['entity_test']['indexes'], $actual_value['indexes']);
        $this->assertEquals($expected['entity_test']['fields'], $actual_value['fields']);
        // If the parameters don't match, the assertions above will throw an
        // exception.
        return TRUE;
      }));

    $this->assertNull(
      $this->storageSchema->onEntityTypeUpdate($this->entityType, $original_entity_type)
    );
  }

}
