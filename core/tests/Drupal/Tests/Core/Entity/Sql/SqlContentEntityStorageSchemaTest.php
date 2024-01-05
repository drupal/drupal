<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\Sql;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema
 * @group Entity
 */
class SqlContentEntityStorageSchemaTest extends UnitTestCase {

  /**
   * The mocked DB schema handler.
   *
   * @var \Drupal\Core\Database\Schema|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $dbSchemaHandler;

  /**
   * The mocked entity type manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The mocked entity field manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityFieldManager;

  /**
   * The mocked entity last installed schema repository used in this test.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * The mocked entity type used in this test.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $entityType;

  /**
   * The mocked SQL storage used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The mocked field definitions used in this test.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
   */
  protected $storageDefinitions;

  /**
   * The storage schema handler used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storageSchema;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManager::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManager::class);
    $this->entityLastInstalledSchemaRepository = $this->createMock(EntityLastInstalledSchemaRepositoryInterface::class);
    $this->storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storage->expects($this->any())
      ->method('getBaseTable')
      ->willReturn('entity_test');

    // Add an ID field. This also acts as a test for a simple, single-column
    // field.
    $this->setUpStorageDefinition('id', [
      'columns' => [
        'value' => [
          'type' => 'int',
        ],
      ],
    ]);
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
   * @covers ::processIdentifierSchema
   */
  public function testGetSchemaBase() {
    $this->entityType = new ContentEntityType([
      'id' => 'entity_test',
      'entity_keys' => ['id' => 'id'],
    ]);

    // Add a field with a 'length' constraint.
    $this->setUpStorageDefinition('name', [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ]);
    // Add a multi-column field.
    $this->setUpStorageDefinition('description', [
      'columns' => [
        'value' => [
          'type' => 'text',
        ],
        'format' => [
          'type' => 'varchar',
        ],
      ],
    ]);
    // Add a field with a unique key.
    $this->setUpStorageDefinition('uuid', [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 128,
        ],
      ],
      'unique keys' => [
        'value' => ['value'],
      ],
    ]);
    // Add a field with a unique key, specified as column name and length.
    $this->setUpStorageDefinition('hash', [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 20,
        ],
      ],
      'unique keys' => [
        'value' => [['value', 10]],
      ],
    ]);
    // Add a field with a multi-column unique key.
    $this->setUpStorageDefinition('email', [
      'columns' => [
        'username' => [
          'type' => 'varchar',
        ],
        'hostname' => [
          'type' => 'varchar',
        ],
        'domain' => [
          'type' => 'varchar',
        ],
      ],
      'unique keys' => [
        'email' => ['username', 'hostname', ['domain', 3]],
      ],
    ]);
    // Add a field with an index.
    $this->setUpStorageDefinition('owner', [
      'columns' => [
        'target_id' => [
          'type' => 'int',
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ]);
    // Add a field with an index, specified as column name and length.
    $this->setUpStorageDefinition('translator', [
      'columns' => [
        'target_id' => [
          'type' => 'int',
        ],
      ],
      'indexes' => [
        'target_id' => [['target_id', 10]],
      ],
    ]);
    // Add a field with a multi-column index.
    $this->setUpStorageDefinition('location', [
      'columns' => [
        'country' => [
          'type' => 'varchar',
        ],
        'state' => [
          'type' => 'varchar',
        ],
        'city' => [
          'type' => 'varchar',
        ],
      ],
      'indexes' => [
        'country_state_city' => ['country', 'state', ['city', 10]],
      ],
    ]);
    // Add a field with a foreign key.
    $this->setUpStorageDefinition('editor', [
      'columns' => [
        'target_id' => [
          'type' => 'int',
        ],
      ],
      'foreign keys' => [
        'user_id' => [
          'table' => 'users',
          'columns' => ['target_id' => 'uid'],
        ],
      ],
    ]);
    // Add a multi-column field with a foreign key.
    $this->setUpStorageDefinition('editor_revision', [
      'columns' => [
        'target_id' => [
          'type' => 'int',
        ],
        'target_revision_id' => [
          'type' => 'int',
        ],
      ],
      'foreign keys' => [
        'user_id' => [
          'table' => 'users',
          'columns' => ['target_id' => 'uid'],
        ],
      ],
    ]);
    // Add a field with a really long index.
    $this->setUpStorageDefinition('long_index_name', [
      'columns' => [
        'long_index_name' => [
          'type' => 'int',
        ],
      ],
      'indexes' => [
        'long_index_name_really_long_long_name' => [['long_index_name', 10]],
      ],
    ]);

    $expected = [
      'entity_test' => [
        'description' => 'The base table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'name' => [
            'type' => 'varchar',
            'length' => 255,
            'not null' => FALSE,
          ],
          'description__value' => [
            'type' => 'text',
            'not null' => FALSE,
          ],
          'description__format' => [
            'type' => 'varchar',
            'not null' => FALSE,
          ],
          'uuid' => [
            'type' => 'varchar',
            'length' => 128,
            'not null' => FALSE,
          ],
          'hash' => [
            'type' => 'varchar',
            'length' => 20,
            'not null' => FALSE,
          ],
          'email__username' => [
            'type' => 'varchar',
            'not null' => FALSE,
          ],
          'email__hostname' => [
            'type' => 'varchar',
            'not null' => FALSE,
          ],
          'email__domain' => [
            'type' => 'varchar',
            'not null' => FALSE,
          ],
          'owner' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
          'translator' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
          'location__country' => [
            'type' => 'varchar',
            'not null' => FALSE,
          ],
          'location__state' => [
            'type' => 'varchar',
            'not null' => FALSE,
          ],
          'location__city' => [
            'type' => 'varchar',
            'not null' => FALSE,
          ],
          'editor' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
          'editor_revision__target_id' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
          'editor_revision__target_revision_id' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
          'long_index_name' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
        ],
        'primary key' => ['id'],
        'unique keys' => [
          'entity_test_field__uuid__value' => ['uuid'],
          'entity_test_field__hash__value' => [['hash', 10]],
          'entity_test_field__email__email' => [
            'email__username',
            'email__hostname',
            ['email__domain', 3],
          ],
        ],
        'indexes' => [
          'entity_test_field__owner__target_id' => ['owner'],
          'entity_test_field__translator__target_id' => [
            ['translator', 10],
          ],
          'entity_test_field__location__country_state_city' => [
            'location__country',
            'location__state',
            ['location__city', 10],
          ],
          'entity_test__b588603cb9' => [
            ['long_index_name', 10],
          ],

        ],
        'foreign keys' => [
          'entity_test_field__editor__user_id' => [
            'table' => 'users',
            'columns' => ['editor' => 'uid'],
          ],
          'entity_test_field__editor_revision__user_id' => [
            'table' => 'users',
            'columns' => ['editor_revision__target_id' => 'uid'],
          ],
        ],
      ],
    ];

    $this->setUpStorageSchema($expected);

    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns('entity_test', ['default_langcode']);

    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

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
   * @covers ::processIdentifierSchema
   */
  public function testGetSchemaRevisionable() {
    $this->entityType = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityType')
      ->setConstructorArgs([
        [
          'id' => 'entity_test',
          'entity_keys' => [
            'id' => 'id',
            'revision' => 'revision_id',
          ],
        ],
      ])
      ->onlyMethods(['getRevisionMetadataKeys'])
      ->getMock();

    $this->entityType->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn([]);

    $this->storage->expects($this->exactly(9))
      ->method('getRevisionTable')
      ->willReturn('entity_test_revision');

    $this->setUpStorageDefinition('revision_id', [
      'columns' => [
        'value' => [
          'type' => 'int',
        ],
      ],
    ]);

    $expected = [
      'entity_test' => [
        'description' => 'The base table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'revision_id' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
        ],
        'primary key' => ['id'],
        'unique keys' => [
          'entity_test__revision_id' => ['revision_id'],
        ],
        'indexes' => [],
        'foreign keys' => [
          'entity_test__revision' => [
            'table' => 'entity_test_revision',
            'columns' => ['revision_id' => 'revision_id'],
          ],
        ],
      ],
      'entity_test_revision' => [
        'description' => 'The revision table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'revision_id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['revision_id'],
        'unique keys' => [],
        'indexes' => [
          'entity_test__id' => ['id'],
        ],
        'foreign keys' => [
          'entity_test__revisioned' => [
            'table' => 'entity_test',
            'columns' => ['id' => 'id'],
          ],
        ],
      ],
    ];

    $this->setUpStorageSchema($expected);

    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_revision', array_keys($this->storageDefinitions));

    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

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
    $this->entityType = new ContentEntityType([
      'id' => 'entity_test',
      'entity_keys' => [
        'id' => 'id',
        'langcode' => 'langcode',
      ],
      'translatable' => TRUE,
    ]);

    $this->storage->expects($this->any())
      ->method('getDataTable')
      ->willReturn('entity_test_field_data');

    $this->setUpStorageDefinition('langcode', [
      'columns' => [
        'value' => [
          'type' => 'varchar',
        ],
      ],
    ]);

    $this->setUpStorageDefinition('default_langcode', [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ]);

    $expected = [
      'entity_test' => [
        'description' => 'The base table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'langcode' => [
            'type' => 'varchar',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id'],
        'unique keys' => [],
        'indexes' => [],
        'foreign keys' => [],
      ],
      'entity_test_field_data' => [
        'description' => 'The data table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'langcode' => [
            'type' => 'varchar',
            'not null' => TRUE,
          ],
          'default_langcode' => [
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id', 'langcode'],
        'unique keys' => [],
        'indexes' => [
          'entity_test__id__default_langcode__langcode' => [
            0 => 'id',
            1 => 'default_langcode',
            2 => 'langcode',
          ],
        ],
        'foreign keys' => [
          'entity_test' => [
            'table' => 'entity_test',
            'columns' => ['id' => 'id'],
          ],
        ],
      ],
    ];

    $this->setUpStorageSchema($expected);

    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $non_data_fields = array_keys($this->storageDefinitions);
    unset($non_data_fields[array_search('default_langcode', $non_data_fields)]);
    $table_mapping->setFieldNames('entity_test', $non_data_fields);
    $table_mapping->setFieldNames('entity_test_field_data', array_keys($this->storageDefinitions));

    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

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
    $this->entityType = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityType')
      ->setConstructorArgs([
        [
          'id' => 'entity_test',
          'entity_keys' => [
            'id' => 'id',
            'revision' => 'revision_id',
            'langcode' => 'langcode',
          ],
          'revision_data_table' => 'entity_test_revision_field_data',
        ],
      ])
      ->onlyMethods(['isRevisionable', 'isTranslatable', 'getRevisionMetadataKeys'])
      ->getMock();

    $this->entityType->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(TRUE);

    $this->storage->expects($this->exactly(30))
      ->method('getRevisionTable')
      ->willReturn('entity_test_revision');

    $this->setUpStorageDefinition('revision_id', [
      'columns' => [
        'value' => [
          'type' => 'int',
        ],
      ],
    ]);
    $this->setUpStorageDefinition('langcode', [
      'columns' => [
        'value' => [
          'type' => 'varchar',
        ],
      ],
    ]);
    $this->setUpStorageDefinition('default_langcode', [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ]);

    $expected = [
      'entity_test' => [
        'description' => 'The base table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'revision_id' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
          'langcode' => [
            'type' => 'varchar',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id'],
        'unique keys' => [
          'entity_test__revision_id' => ['revision_id'],
        ],
        'indexes' => [],
        'foreign keys' => [
          'entity_test__revision' => [
            'table' => 'entity_test_revision',
            'columns' => ['revision_id' => 'revision_id'],
          ],
        ],
      ],
      'entity_test_revision' => [
        'description' => 'The revision table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'revision_id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'langcode' => [
            'type' => 'varchar',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['revision_id'],
        'unique keys' => [],
        'indexes' => [
          'entity_test__id' => ['id'],
        ],
        'foreign keys' => [
          'entity_test__revisioned' => [
            'table' => 'entity_test',
            'columns' => ['id' => 'id'],
          ],
        ],
      ],
      'entity_test_field_data' => [
        'description' => 'The data table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'revision_id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'langcode' => [
            'type' => 'varchar',
            'not null' => TRUE,
          ],
          'default_langcode' => [
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id', 'langcode'],
        'unique keys' => [],
        'indexes' => [
          'entity_test__revision_id' => ['revision_id'],
          'entity_test__id__default_langcode__langcode' => [
            0 => 'id',
            1 => 'default_langcode',
            2 => 'langcode',
          ],
        ],
        'foreign keys' => [
          'entity_test' => [
            'table' => 'entity_test',
            'columns' => ['id' => 'id'],
          ],
        ],
      ],
      'entity_test_revision_field_data' => [
        'description' => 'The revision data table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'revision_id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'langcode' => [
            'type' => 'varchar',
            'not null' => TRUE,
          ],
          'default_langcode' => [
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['revision_id', 'langcode'],
        'unique keys' => [],
        'indexes' => [
          'entity_test__id__default_langcode__langcode' => [
            0 => 'id',
            1 => 'default_langcode',
            2 => 'langcode',
          ],
        ],
        'foreign keys' => [
          'entity_test' => [
            'table' => 'entity_test',
            'columns' => ['id' => 'id'],
          ],
          'entity_test__revision' => [
            'table' => 'entity_test_revision',
            'columns' => ['revision_id' => 'revision_id'],
          ],
        ],
      ],
    ];

    $this->setUpStorageSchema($expected);

    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $non_data_fields = array_keys($this->storageDefinitions);
    unset($non_data_fields[array_search('default_langcode', $non_data_fields)]);
    $table_mapping->setFieldNames('entity_test', $non_data_fields);
    $table_mapping->setFieldNames('entity_test_revision', $non_data_fields);
    $table_mapping->setFieldNames('entity_test_field_data', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_revision_field_data', array_keys($this->storageDefinitions));

    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

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
    $this->entityType = new ContentEntityType([
      'id' => 'entity_test',
      'entity_keys' => ['id' => 'id'],
    ]);

    // Setup a field having a dedicated schema.
    $field_name = $this->getRandomGenerator()->name();
    $this->setUpStorageDefinition($field_name, [
      'columns' => [
        'shape' => [
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ],
        'color' => [
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ],
        'area' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'depth' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
      'foreign keys' => [
        'color' => [
          'table' => 'color',
          'columns' => [
            'color' => 'id',
          ],
        ],
      ],
      'unique keys' => [
        'area' => ['area'],
        'shape' => [['shape', 10]],
      ],
      'indexes' => [
        'depth' => ['depth'],
        'color' => [['color', 3]],
      ],
    ]);

    $field_storage = $this->storageDefinitions[$field_name];
    $field_storage
      ->expects($this->any())
      ->method('getType')
      ->willReturn('shape');
    $field_storage
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->willReturn($entity_type_id);
    $field_storage
      ->expects($this->any())
      ->method('isMultiple')
      ->willReturn(TRUE);

    $this->storageDefinitions['id']
      ->expects($this->any())
      ->method('getType')
      ->willReturn('integer');

    $expected = [
      $entity_type_id . '__' . $field_name => [
        'description' => "Data storage for $entity_type_id field $field_name.",
        'fields' => [
          'bundle' => [
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ],
          'deleted' => [
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ],
          'entity_id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The entity id this data is attached to',
          ],
          'revision_id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id',
          ],
          'langcode' => [
            'type' => 'varchar_ascii',
            'length' => 32,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The language code for this data item.',
          ],
          'delta' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ],
          $field_name . '_shape' => [
            'type' => 'varchar',
            'length' => 32,
            'not null' => FALSE,
          ],
          $field_name . '_color' => [
            'type' => 'varchar',
            'length' => 32,
            'not null' => FALSE,
          ],
          $field_name . '_area' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
          $field_name . '_depth' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['entity_id', 'deleted', 'delta', 'langcode'],
        'indexes' => [
          'bundle' => ['bundle'],
          'revision_id' => ['revision_id'],
          $field_name . '_depth' => [$field_name . '_depth'],
          $field_name . '_color' => [[$field_name . '_color', 3]],
        ],
        'unique keys' => [
          $field_name . '_area' => [$field_name . '_area'],
          $field_name . '_shape' => [[$field_name . '_shape', 10]],
        ],
        'foreign keys' => [
          $field_name . '_color' => [
            'table' => 'color',
            'columns' => [
              $field_name . '_color' => 'id',
            ],
          ],
        ],
      ],
    ];

    $this->setUpStorageSchema($expected);

    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames($entity_type_id, array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns($entity_type_id, ['default_langcode']);

    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

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
    $this->entityType = new ContentEntityType([
      'id' => 'entity_test',
      'entity_keys' => ['id' => 'id'],
    ]);

    // Setup a field having a dedicated schema.
    $field_name = $this->getRandomGenerator()->name();
    $this->setUpStorageDefinition($field_name, [
      'columns' => [
        'shape' => [
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ],
        'color' => [
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ],
      ],
      'foreign keys' => [
        'color' => [
          'table' => 'color',
          'columns' => [
            'color' => 'id',
          ],
        ],
      ],
      'unique keys' => [],
      'indexes' => [],
    ]);

    $field_storage = $this->storageDefinitions[$field_name];
    $field_storage
      ->expects($this->any())
      ->method('getType')
      ->willReturn('shape');
    $field_storage
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->willReturn($entity_type_id);
    $field_storage
      ->expects($this->any())
      ->method('isMultiple')
      ->willReturn(TRUE);

    $this->storageDefinitions['id']
      ->expects($this->any())
      ->method('getType')
      ->willReturn('string');

    $expected = [
      $entity_type_id . '__' . $field_name => [
        'description' => "Data storage for $entity_type_id field $field_name.",
        'fields' => [
          'bundle' => [
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ],
          'deleted' => [
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ],
          'entity_id' => [
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => TRUE,
            'description' => 'The entity id this data is attached to',
          ],
          'revision_id' => [
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => TRUE,
            'description' => 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id',
          ],
          'langcode' => [
            'type' => 'varchar_ascii',
            'length' => 32,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The language code for this data item.',
          ],
          'delta' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ],
          $field_name . '_shape' => [
            'type' => 'varchar',
            'length' => 32,
            'not null' => FALSE,
          ],
          $field_name . '_color' => [
            'type' => 'varchar',
            'length' => 32,
            'not null' => FALSE,
          ],
        ],
        'primary key' => ['entity_id', 'deleted', 'delta', 'langcode'],
        'indexes' => [
          'bundle' => ['bundle'],
          'revision_id' => ['revision_id'],
        ],
        'foreign keys' => [
          $field_name . '_color' => [
            'table' => 'color',
            'columns' => [
              $field_name . '_color' => 'id',
            ],
          ],
        ],
      ],
    ];

    $this->setUpStorageSchema($expected);

    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames($entity_type_id, array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns($entity_type_id, ['default_langcode']);

    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    $this->assertNull(
      $this->storageSchema->onFieldStorageDefinitionCreate($field_storage)
    );
  }

  public function providerTestRequiresEntityDataMigration() {
    $updated_entity_type_definition = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $updated_entity_type_definition->expects($this->any())
      ->method('getStorageClass')
      // A class that exists, *any* class.
      ->willReturn('\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema');
    $original_entity_type_definition = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $original_entity_type_definition->expects($this->any())
      ->method('getStorageClass')
      // A class that exists, *any* class.
      ->willReturn('\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema');
    $original_entity_type_definition_other_nonexisting = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $original_entity_type_definition_other_nonexisting->expects($this->any())
      ->method('getStorageClass')
      ->willReturn('bar');
    $original_entity_type_definition_other_existing = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
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
      // ::hasData() === TRUE, no structure changes.
      [$updated_entity_type_definition, $original_entity_type_definition_other_existing, TRUE, FALSE, FALSE],
    ];
  }

  /**
   * @covers ::requiresEntityDataMigration
   *
   * @dataProvider providerTestRequiresEntityDataMigration
   */
  public function testRequiresEntityDataMigration($updated_entity_type_definition, $original_entity_type_definition, $original_storage_has_data, $shared_table_structure_changed, $migration_required) {
    $this->entityType = new ContentEntityType([
      'id' => 'entity_test',
      'entity_keys' => ['id' => 'id'],
    ]);

    $this->storage->expects($this->exactly(is_null($original_storage_has_data) || !$shared_table_structure_changed ? 0 : 1))
      ->method('hasData')
      ->willReturn($original_storage_has_data);

    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityLastInstalledSchemaRepository
      ->expects($this->any())
      ->method('getLastInstalledDefinition')
      ->willReturn($this->entityType);
    $this->entityLastInstalledSchemaRepository
      ->expects($this->any())
      ->method('getLastInstalledFieldStorageDefinitions')
      ->willReturn($this->storageDefinitions);

    $this->storageSchema = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema')
      ->setConstructorArgs([$this->entityTypeManager->reveal(), $this->entityType, $this->storage, $connection, $this->entityFieldManager->reveal(), $this->entityLastInstalledSchemaRepository])
      ->onlyMethods(['installedStorageSchema', 'hasSharedTableStructureChange'])
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

    $updated_entity_type_definition = $this->createMock('\Drupal\Core\Entity\ContentEntityTypeInterface');
    $original_entity_type_definition = $this->createMock('\Drupal\Core\Entity\ContentEntityTypeInterface');

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

    $this->entityType = new ContentEntityType([
      'id' => 'entity_test',
      'entity_keys' => ['id' => 'id'],
    ]);

    $this->setUpStorageSchema();
    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns('entity_test', ['default_langcode']);
    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    // Setup storage schema.
    if ($change_schema) {
      $this->storageSchema->expects($this->once())
        ->method('loadEntitySchemaData')
        ->willReturn([]);
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
  protected function setUpStorageSchema(array $expected = []) {
    $this->entityTypeManager
      ->getDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $this->entityTypeManager
      ->getActiveDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $this->entityFieldManager
      ->getFieldStorageDefinitions($this->entityType->id())
      ->willReturn($this->storageDefinitions);

    $this->entityFieldManager
      ->getActiveFieldStorageDefinitions($this->entityType->id())
      ->willReturn($this->storageDefinitions);

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
          $this->callback(function ($table_name) use (&$invocation_count, $expected_table_names) {
            return $expected_table_names[$invocation_count] == $table_name;
          }),
          $this->callback(function ($table_schema) use (&$invocation_count, $expected_table_schemas) {
            return $expected_table_schemas[$invocation_count] == $table_schema;
          })
        )
        ->willReturnCallback(function () use (&$invocation_count) {
          $invocation_count++;
        });
    }

    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $connection->expects($this->any())
      ->method('schema')
      ->willReturn($this->dbSchemaHandler);

    $key_value = $this->createMock('Drupal\Core\KeyValueStore\KeyValueStoreInterface');

    $this->entityLastInstalledSchemaRepository
      ->expects($this->any())
      ->method('getLastInstalledDefinition')
      ->willReturn($this->entityType);
    $this->entityLastInstalledSchemaRepository
      ->expects($this->any())
      ->method('getLastInstalledFieldStorageDefinitions')
      ->willReturn($this->storageDefinitions);

    $this->storageSchema = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema')
      ->setConstructorArgs([$this->entityTypeManager->reveal(), $this->entityType, $this->storage, $connection, $this->entityFieldManager->reveal(), $this->entityLastInstalledSchemaRepository])
      ->onlyMethods(['installedStorageSchema', 'loadEntitySchemaData', 'hasSharedTableNameChanges', 'isTableEmpty', 'getTableMapping'])
      ->getMock();
    $this->storageSchema
      ->expects($this->any())
      ->method('installedStorageSchema')
      ->willReturn($key_value);
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
    $this->storageDefinitions[$field_name] = $this->createMock('Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface');
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('isBaseField')
      ->willReturn(TRUE);
    // getName() is called once for each table.
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getName')
      ->willReturn($field_name);
    // getSchema() is called once for each table.
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getSchema')
      ->willReturn($schema);
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getColumns')
      ->willReturn($schema['columns']);
    // Add property definitions.
    if (!empty($schema['columns'])) {
      $property_definitions = [];
      foreach ($schema['columns'] as $column => $info) {
        $property_definitions[$column] = $this->createMock('Drupal\Core\TypedData\DataDefinitionInterface');
        $property_definitions[$column]->expects($this->any())
          ->method('isRequired')
          ->willReturn(!empty($info['not null']));
      }
      $this->storageDefinitions[$field_name]->expects($this->any())
        ->method('getPropertyDefinitions')
        ->willReturn($property_definitions);
    }
  }

  /**
   * ::onEntityTypeUpdate.
   */
  public function testonEntityTypeUpdateWithNewIndex() {
    $this->entityType = $original_entity_type = new ContentEntityType([
      'id' => 'entity_test',
      'entity_keys' => ['id' => 'id'],
    ]);

    // Add a field with a really long index.
    $this->setUpStorageDefinition('long_index_name', [
      'columns' => [
        'long_index_name' => [
          'type' => 'int',
        ],
      ],
      'indexes' => [
        'long_index_name_really_long_long_name' => [['long_index_name', 10]],
      ],
    ]);

    $expected = [
      'entity_test' => [
        'description' => 'The base table for entity_test entities.',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'long_index_name' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
        ],
        'indexes' => [
          'entity_test__b588603cb9' => [
            ['long_index_name', 10],
          ],
        ],
      ],
    ];

    $this->setUpStorageSchema($expected);

    $table_mapping = new TestSqlContentDefaultTableMapping($this->entityType, $this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns('entity_test', ['default_langcode']);

    $this->storageSchema->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

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
    $this->dbSchemaHandler->expects($this->exactly(3))
      ->method('dropIndex')
      ->withConsecutive(
        ['entity_test', 'entity_test__b588603cb9'],
        ['entity_test', 'entity_test__removed_field'],
      );

    $this->dbSchemaHandler->expects($this->atLeastOnce())
      ->method('fieldExists')
      ->willReturn(TRUE);
    $this->dbSchemaHandler->expects($this->atLeastOnce())
      ->method('addIndex')
      ->with('entity_test', 'entity_test__b588603cb9', [['long_index_name', 10]], $this->callback(function ($actual_value) use ($expected) {
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

  /**
   * Tests various value casts depending on column schema.
   *
   * @param mixed $expected
   *   The expected value.
   * @param mixed $value
   *   The tested value.
   * @param array $schema
   *   The schema for the table column.
   *
   * @dataProvider providerSchemaCastValue
   * @covers ::castValue
   */
  public function testCastValue($expected, $value, array $schema) {
    $this->assertSame($expected, SqlContentEntityStorageSchema::castValue($schema, $value));
  }

  /**
   * Provides data for testCastValue().
   */
  public function providerSchemaCastValue() {
    $cases = [];
    // Tests NULL values.
    $cases[] = [
      NULL,
      NULL,
      [
        'not null' => FALSE,
      ],
    ];
    $cases[] = [
      0,
      NULL,
      [
        'not null' => TRUE,
        'type' => 'int',
      ],
    ];
    $cases[] = [
      0,
      NULL,
      [
        'not null' => TRUE,
        'type' => 'serial',
      ],
    ];
    $cases[] = [
      0.0,
      NULL,
      [
        'not null' => TRUE,
        'type' => 'float',
      ],
    ];
    $cases[] = [
      '',
      NULL,
      [
        'not null' => TRUE,
        'type' => 'varchar',
      ],
    ];
    // Tests cast to int and serial.
    $cases[] = [
      1,
      '1.001',
      [
        'type' => 'int',
      ],
    ];
    $cases[] = [
      2,
      2.6,
      [
        'type' => 'int',
      ],
    ];
    $cases[] = [
      3,
      '3.6',
      [
        'type' => 'serial',
      ],
    ];
    // Tests float.
    $cases[] = [
      1.001,
      '1.001',
      [
        'type' => 'float',
      ],
    ];
    $cases[] = [
      2.6,
      2.6,
      [
        'type' => 'float',
      ],
    ];
    // Tests other column types casts to string.
    $cases[] = [
      '1',
      1,
      [
        'type' => 'varchar',
      ],
    ];
    $cases[] = [
      '2',
      '2',
      [
        'type' => 'varchar',
      ],
    ];
    return $cases;
  }

}

/**
 * Extends DefaultTableMapping to allow calling its protected methods.
 */
class TestSqlContentDefaultTableMapping extends DefaultTableMapping {

  /**
   * {@inheritdoc}
   */
  public function setFieldNames($table_name, array $field_names) {
    return parent::setFieldNames($table_name, $field_names);
  }

  /**
   * {@inheritdoc}
   */
  public function setExtraColumns($table_name, array $column_names) {
    return parent::setExtraColumns($table_name, $column_names);
  }

}
