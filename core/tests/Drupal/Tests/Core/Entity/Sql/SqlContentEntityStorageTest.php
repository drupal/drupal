<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\Sql;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\Language;
use Drupal\Tests\Core\Entity\ContentEntityBaseMockableClass;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Sql\SqlContentEntityStorage
 * @group Entity
 */
class SqlContentEntityStorageTest extends UnitTestCase {

  /**
   * The content entity database storage used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityStorage;

  /**
   * The mocked entity type used in this test.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * An array of field definitions used for this test, keyed by field name.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]|\PHPUnit\Framework\MockObject\MockObject[]
   */
  protected $fieldDefinitions = [];

  /**
   * The mocked entity type manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked entity type bundle info used in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeBundleInfo;

  /**
   * The mocked entity field manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test';

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityType = $this->createMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('id')
      ->willReturn($this->entityTypeId);

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $this->entityTypeManager = $this->prophesize(EntityTypeManager::class);
    $this->entityTypeBundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManager::class);
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->willReturn(new Language(['langcode' => 'en']));
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $this->container->set('entity_field.manager', $this->entityFieldManager->reveal());
  }

  /**
   * Tests SqlContentEntityStorage::getBaseTable().
   *
   * @param string $base_table
   *   The base table to be returned by the mocked entity type.
   * @param string $expected
   *   The expected return value of
   *   SqlContentEntityStorage::getBaseTable().
   *
   * @covers ::__construct
   * @covers ::getBaseTable
   *
   * @dataProvider providerTestGetBaseTable
   */
  public function testGetBaseTable($base_table, $expected): void {
    $this->entityType->expects($this->once())
      ->method('getBaseTable')
      ->willReturn($base_table);

    $this->setUpEntityStorage();

    $this->assertSame($expected, $this->entityStorage->getBaseTable());
  }

  /**
   * Provides test data for testGetBaseTable().
   *
   * @return array[]
   *   A nested array where each inner array has the base table to be returned
   *   by the mocked entity type as the first value and the expected return
   *   value of SqlContentEntityStorage::getBaseTable() as the second
   *   value.
   */
  public static function providerTestGetBaseTable() {
    return [
      // Test that the entity type's base table is used, if provided.
      ['entity_test', 'entity_test'],
      // Test that the storage falls back to the entity type ID.
      [NULL, 'entity_test'],
    ];
  }

  /**
   * Tests SqlContentEntityStorage::getRevisionTable().
   *
   * @param string $revision_table
   *   The revision table to be returned by the mocked entity type.
   * @param string $expected
   *   The expected return value of
   *   SqlContentEntityStorage::getRevisionTable().
   *
   * @covers ::__construct
   * @covers ::getRevisionTable
   *
   * @dataProvider providerTestGetRevisionTable
   */
  public function testGetRevisionTable($revision_table, $expected): void {
    $this->entityType->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->once())
      ->method('getRevisionTable')
      ->willReturn($revision_table);
    $this->entityType->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn([]);

    $this->setUpEntityStorage();

    $this->assertSame($expected, $this->entityStorage->getRevisionTable());
  }

  /**
   * Provides test data for testGetRevisionTable().
   *
   * @return array[]
   *   A nested array where each inner array has the revision table to be
   *   returned by the mocked entity type as the first value and the expected
   *   return value of SqlContentEntityStorage::getRevisionTable() as the
   *   second value.
   */
  public static function providerTestGetRevisionTable() {
    return [
      // Test that the entity type's revision table is used, if provided.
      ['entity_test_revision', 'entity_test_revision'],
      // Test that the storage falls back to the entity type ID with a
      // '_revision' suffix.
      [NULL, 'entity_test_revision'],
    ];
  }

  /**
   * Tests SqlContentEntityStorage::getDataTable().
   *
   * @covers ::__construct
   * @covers ::getDataTable
   */
  public function testGetDataTable(): void {
    $this->entityType->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->exactly(1))
      ->method('getDataTable')
      ->willReturn('entity_test_field_data');
    $this->entityType->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn([]);

    $this->setUpEntityStorage();

    $this->assertSame('entity_test_field_data', $this->entityStorage->getDataTable());
  }

  /**
   * Tests SqlContentEntityStorage::getRevisionDataTable().
   *
   * @param string $revision_data_table
   *   The revision data table to be returned by the mocked entity type.
   * @param string $expected
   *   The expected return value of
   *   SqlContentEntityStorage::getRevisionDataTable().
   *
   * @covers ::__construct
   * @covers ::getRevisionDataTable
   *
   * @dataProvider providerTestGetRevisionDataTable
   */
  public function testGetRevisionDataTable($revision_data_table, $expected): void {
    $this->entityType->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->exactly(1))
      ->method('getDataTable')
      ->willReturn('entity_test_field_data');
    $this->entityType->expects($this->once())
      ->method('getRevisionDataTable')
      ->willReturn($revision_data_table);
    $this->entityType->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn([]);

    $this->setUpEntityStorage();

    $actual = $this->entityStorage->getRevisionDataTable();
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides test data for testGetRevisionDataTable().
   *
   * @return array[]
   *   A nested array where each inner array has the revision data table to be
   *   returned by the mocked entity type as the first value and the expected
   *   return value of SqlContentEntityStorage::getRevisionDataTable() as
   *   the second value.
   */
  public static function providerTestGetRevisionDataTable() {
    return [
      // Test that the entity type's revision data table is used, if provided.
      ['entity_test_field_revision', 'entity_test_field_revision'],
      // Test that the storage falls back to the entity type ID with a
      // '_field_revision' suffix.
      [NULL, 'entity_test_field_revision'],
    ];
  }

  /**
   * Tests that setting a new table mapping also updates the table names.
   *
   * @covers ::setTableMapping
   */
  public function testSetTableMapping(): void {
    $this->entityType->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(FALSE);
    $this->entityType->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(FALSE);
    $this->entityType->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn([]);

    $this->setUpEntityStorage();

    $this->assertSame('entity_test', $this->entityStorage->getBaseTable());
    $this->assertNull($this->entityStorage->getRevisionTable());
    $this->assertNull($this->entityStorage->getDataTable());
    $this->assertNull($this->entityStorage->getRevisionDataTable());

    // Change the entity type definition and instantiate a new table mapping
    // with it.
    $updated_entity_type = $this->createMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $updated_entity_type->expects($this->any())
      ->method('id')
      ->willReturn($this->entityTypeId);
    $updated_entity_type->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $updated_entity_type->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(TRUE);

    $table_mapping = new DefaultTableMapping($updated_entity_type, []);
    $this->entityStorage->setTableMapping($table_mapping);

    $this->assertSame('entity_test', $this->entityStorage->getBaseTable());
    $this->assertSame('entity_test_revision', $this->entityStorage->getRevisionTable());
    $this->assertSame('entity_test_field_data', $this->entityStorage->getDataTable());
    $this->assertSame('entity_test_field_revision', $this->entityStorage->getRevisionDataTable());
  }

  /**
   * Tests ContentEntityDatabaseStorage::onEntityTypeCreate().
   *
   * @covers ::__construct
   * @covers ::onEntityTypeCreate
   * @covers ::getTableMapping
   */
  public function testOnEntityTypeCreate(): void {
    $columns = [
      'value' => [
        'type' => 'int',
      ],
    ];

    $this->fieldDefinitions = $this->mockFieldDefinitions(['id']);
    $this->fieldDefinitions['id']->expects($this->any())
      ->method('getColumns')
      ->willReturn($columns);
    $this->fieldDefinitions['id']->expects($this->once())
      ->method('getSchema')
      ->willReturn(['columns' => $columns]);

    $this->entityType->expects($this->once())
      ->method('getKeys')
      ->willReturn(['id' => 'id']);
    $this->entityType->expects($this->any())
      ->method('hasKey')
      ->willReturnMap([
        // SqlContentEntityStorageSchema::initializeBaseTable()
        ['revision', FALSE],
        ['id', TRUE],
      ]);
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        // EntityStorageBase::__construct()
        ['id', 'id'],
        // ContentEntityStorageBase::__construct()
        ['uuid', NULL],
        ['bundle', NULL],
        // SqlContentEntityStorageSchema::initializeBaseTable()
        ['id' => 'id'],
        ['id' => 'id'],
      ]);

    $this->setUpEntityStorage();

    $expected = [
      'description' => 'The base table for entity_test entities.',
      'fields' => [
        'id' => [
          'type' => 'serial',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
      'unique keys' => [],
      'indexes' => [],
      'foreign keys' => [],
    ];

    $schema_handler = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();
    $schema_handler->expects($this->any())
      ->method('createTable')
      ->with($this->equalTo('entity_test'), $this->equalTo($expected));

    $this->connection->expects($this->once())
      ->method('schema')
      ->willReturn($schema_handler);

    $storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->setConstructorArgs([$this->entityType, $this->connection, $this->entityFieldManager->reveal(), $this->cache, $this->languageManager, new MemoryCache(new Time()), $this->entityTypeBundleInfo, $this->entityTypeManager->reveal()])
      ->onlyMethods(['getStorageSchema'])
      ->getMock();

    $key_value = $this->createMock('Drupal\Core\KeyValueStore\KeyValueStoreInterface');
    $schema_handler = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema')
      ->setConstructorArgs([$this->entityTypeManager->reveal(), $this->entityType, $storage, $this->connection, $this->entityFieldManager->reveal()])
      ->onlyMethods(['installedStorageSchema', 'createSharedTableSchema'])
      ->getMock();
    $schema_handler
      ->expects($this->any())
      ->method('installedStorageSchema')
      ->willReturn($key_value);

    $storage
      ->expects($this->any())
      ->method('getStorageSchema')
      ->willReturn($schema_handler);

    $storage->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests getTableMapping() with an empty entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   */
  public function testGetTableMappingEmpty(): void {
    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();
    $this->assertSame(['entity_test'], $mapping->getTableNames());
    $this->assertSame([], $mapping->getFieldNames('entity_test'));
    $this->assertSame([], $mapping->getExtraColumns('entity_test'));
  }

  /**
   * Tests getTableMapping() with a simple entity type.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingSimple(array $entity_keys): void {
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['id', $entity_keys['id']],
        ['uuid', $entity_keys['uuid']],
        ['bundle', $entity_keys['bundle']],
      ]);

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $this->assertEquals(['entity_test'], $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $this->assertEquals($expected, $mapping->getFieldNames('entity_test'));

    $this->assertEquals([], $mapping->getExtraColumns('entity_test'));
  }

  /**
   * Tests getTableMapping() with a simple entity type with some base fields.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingSimpleWithFields(array $entity_keys): void {
    $base_field_names = ['title', 'description', 'owner'];
    $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
    $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);
    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();
    $this->assertEquals(['entity_test'], $mapping->getTableNames());
    $this->assertEquals($field_names, $mapping->getFieldNames('entity_test'));
    $this->assertEquals([], $mapping->getExtraColumns('entity_test'));
  }

  /**
   * Provides test data for testGetTableMappingSimple().
   *
   * @return array[]
   *   A nested array, where each inner array has a single value being a  map of
   *   entity keys to use for the mocked entity type.
   */
  public static function providerTestGetTableMappingSimple() {
    return [
      [['id' => 'test_id', 'bundle' => NULL, 'uuid' => NULL]],
      [['id' => 'test_id', 'bundle' => 'test_bundle', 'uuid' => NULL]],
      [['id' => 'test_id', 'bundle' => NULL, 'uuid' => 'test_uuid']],
      [['id' => 'test_id', 'bundle' => 'test_bundle', 'uuid' => 'test_uuid']],
    ];
  }

  /**
   * Tests getTableMapping() with a base field that requires a dedicated table.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   */
  public function testGetTableMappingSimpleWithDedicatedStorageFields(): void {
    $base_field_names = ['multi_valued_base_field'];

    // Set up one entity key in order to have a base table.
    $this->fieldDefinitions = $this->mockFieldDefinitions(['test_id']);

    // Set up the multi-valued base field.
    $this->fieldDefinitions += $this->mockFieldDefinitions($base_field_names, [
      'hasCustomStorage' => FALSE,
      'isMultiple' => TRUE,
      'getTargetEntityTypeId' => 'entity_test',
    ]);

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();
    $this->assertEquals(['entity_test', 'entity_test__multi_valued_base_field'], $mapping->getTableNames());
    $this->assertEquals($base_field_names, $mapping->getFieldNames('entity_test__multi_valued_base_field'));

    $extra_columns = [
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'langcode',
      'delta',
    ];
    $this->assertEquals($extra_columns, $mapping->getExtraColumns('entity_test__multi_valued_base_field'));
  }

  /**
   * Tests getTableMapping() with a revisionable, non-translatable entity type.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingRevisionable(array $entity_keys): void {
    // This allows to re-use the data provider.
    $entity_keys = [
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
    ];

    $this->entityType->expects($this->exactly(4))
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['id', $entity_keys['id']],
        ['uuid', $entity_keys['uuid']],
        ['bundle', $entity_keys['bundle']],
        ['revision', $entity_keys['revision']],
      ]);
    $this->entityType->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn([]);

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = ['entity_test', 'entity_test_revision'];
    $this->assertEquals($expected, $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $this->assertEquals($expected, $mapping->getFieldNames('entity_test'));
    $expected = [$entity_keys['id'], $entity_keys['revision']];
    $this->assertEquals($expected, $mapping->getFieldNames('entity_test_revision'));

    $this->assertEquals([], $mapping->getExtraColumns('entity_test'));
    $this->assertEquals([], $mapping->getExtraColumns('entity_test_revision'));
  }

  /**
   * Tests getTableMapping() with a revisionable entity type with fields.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingRevisionableWithFields(array $entity_keys): void {
    // This allows to re-use the data provider.
    $entity_keys = [
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
    ];

    // PHPUnit does not allow for multiple data providers.
    $test_cases = [
      [],
      ['revision_created' => 'revision_timestamp'],
      ['revision_user' => 'revision_uid'],
      ['revision_log_message' => 'revision_log'],
      ['revision_created' => 'revision_timestamp', 'revision_user' => 'revision_uid'],
      ['revision_created' => 'revision_timestamp', 'revision_log_message' => 'revision_log'],
      ['revision_user' => 'revision_uid', 'revision_log_message' => 'revision_log'],
      ['revision_created' => 'revision_timestamp', 'revision_user' => 'revision_uid', 'revision_log_message' => 'revision_log'],
    ];
    foreach ($test_cases as $revision_metadata_field_names) {
      $this->setUp();

      $base_field_names = ['title'];
      $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
      $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);

      $revisionable_field_names = ['description', 'owner'];
      $field_names = array_merge($field_names, $revisionable_field_names);
      $this->fieldDefinitions += $this->mockFieldDefinitions(array_merge($revisionable_field_names, array_values($revision_metadata_field_names)), ['isRevisionable' => TRUE]);

      $this->entityType->expects($this->exactly(4))
        ->method('isRevisionable')
        ->willReturn(TRUE);
      $this->entityType->expects($this->any())
        ->method('getKey')
        ->willReturnMap([
          ['id', $entity_keys['id']],
          ['uuid', $entity_keys['uuid']],
          ['bundle', $entity_keys['bundle']],
          ['revision', $entity_keys['revision']],
        ]);

      $this->entityType->expects($this->any())
        ->method('getRevisionMetadataKeys')
        ->willReturn($revision_metadata_field_names);

      $this->setUpEntityStorage();

      $mapping = $this->entityStorage->getTableMapping();

      $expected = ['entity_test', 'entity_test_revision'];
      $this->assertEquals($expected, $mapping->getTableNames());

      $this->assertEquals($field_names, $mapping->getFieldNames('entity_test'));
      $expected = array_merge(
        [$entity_keys['id'], $entity_keys['revision']],
        $revisionable_field_names,
        array_values($revision_metadata_field_names)
      );
      $this->assertEquals($expected, $mapping->getFieldNames('entity_test_revision'));

      $this->assertEquals([], $mapping->getExtraColumns('entity_test'));
      $this->assertEquals([], $mapping->getExtraColumns('entity_test_revision'));
    }
  }

  /**
   * Tests getTableMapping() with a non-revisionable, translatable entity type.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingTranslatable(array $entity_keys): void {
    // This allows to re-use the data provider.
    $entity_keys['langcode'] = 'langcode';

    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('getDataTable')
      ->willReturn('entity_test_field_data');
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['id', $entity_keys['id']],
        ['uuid', $entity_keys['uuid']],
        ['bundle', $entity_keys['bundle']],
        ['langcode', $entity_keys['langcode']],
      ]);

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = ['entity_test', 'entity_test_field_data'];
    $this->assertEquals($expected, $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $actual = $mapping->getFieldNames('entity_test');
    $this->assertEquals($expected, $actual);
    // The UUID is not stored on the data table.
    $expected = array_values(array_filter([
      $entity_keys['id'],
      $entity_keys['bundle'],
      $entity_keys['langcode'],
    ]));
    $actual = $mapping->getFieldNames('entity_test_field_data');
    $this->assertEquals($expected, $actual);

    $expected = [];
    $actual = $mapping->getExtraColumns('entity_test');
    $this->assertEquals($expected, $actual);
    $actual = $mapping->getExtraColumns('entity_test_field_data');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests getTableMapping() with a translatable entity type with fields.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingTranslatableWithFields(array $entity_keys): void {
    // This allows to re-use the data provider.
    $entity_keys['langcode'] = 'langcode';

    $base_field_names = ['title', 'description', 'owner'];
    $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
    $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);

    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('getDataTable')
      ->willReturn('entity_test_field_data');
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['id', $entity_keys['id']],
        ['uuid', $entity_keys['uuid']],
        ['bundle', $entity_keys['bundle']],
        ['langcode', $entity_keys['langcode']],
      ]);

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = ['entity_test', 'entity_test_field_data'];
    $this->assertEquals($expected, $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $actual = $mapping->getFieldNames('entity_test');
    $this->assertEquals($expected, $actual);
    // The UUID is not stored on the data table.
    $expected = array_merge(array_filter([
      $entity_keys['id'],
      $entity_keys['bundle'],
      $entity_keys['langcode'],
    ]), $base_field_names);
    $actual = $mapping->getFieldNames('entity_test_field_data');
    $this->assertEquals($expected, $actual);

    $expected = [];
    $actual = $mapping->getExtraColumns('entity_test');
    $this->assertEquals($expected, $actual);
    $actual = $mapping->getExtraColumns('entity_test_field_data');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests getTableMapping() with a revisionable, translatable entity type.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingRevisionableTranslatable(array $entity_keys): void {
    // This allows to re-use the data provider.
    $entity_keys = [
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
      'langcode' => 'langcode',
    ];
    $revision_metadata_keys = [
      'revision_created' => 'revision_timestamp',
      'revision_user' => 'revision_uid',
      'revision_log_message' => 'revision_log',
    ];

    $this->entityType->expects($this->atLeastOnce())
      ->method('isRevisionable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('getDataTable')
      ->willReturn('entity_test_field_data');
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['id', $entity_keys['id']],
        ['uuid', $entity_keys['uuid']],
        ['bundle', $entity_keys['bundle']],
        ['revision', $entity_keys['revision']],
        ['langcode', $entity_keys['langcode']],
      ]);
    $this->entityType->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn($revision_metadata_keys);

    $this->fieldDefinitions = $this->mockFieldDefinitions(array_values($revision_metadata_keys), ['isRevisionable' => TRUE]);

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = [
      'entity_test',
      'entity_test_field_data',
      'entity_test_revision',
      'entity_test_field_revision',
    ];
    $this->assertEquals($expected, $mapping->getTableNames());

    // The default language code is stored on the base table.
    $expected = array_values(array_filter([
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['bundle'],
      $entity_keys['uuid'],
      $entity_keys['langcode'],
    ]));
    $actual = $mapping->getFieldNames('entity_test');
    $this->assertEquals($expected, $actual);
    // The revision table on the other hand does not store the bundle and the
    // UUID.
    $expected = array_values(array_filter([
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['langcode'],
    ]));
    $expected = array_merge($expected, array_values($revision_metadata_keys));
    $actual = $mapping->getFieldNames('entity_test_revision');
    $this->assertEquals($expected, $actual);
    // The UUID is not stored on the data table.
    $expected = array_values(array_filter([
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['bundle'],
      $entity_keys['langcode'],
    ]));
    $actual = $mapping->getFieldNames('entity_test_field_data');
    $this->assertEquals($expected, $actual);
    // The data revision also does not store the bundle.
    $expected = array_values(array_filter([
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['langcode'],
    ]));
    $actual = $mapping->getFieldNames('entity_test_field_revision');
    $this->assertEquals($expected, $actual);

    $expected = [];
    $actual = $mapping->getExtraColumns('entity_test');
    $this->assertEquals($expected, $actual);
    $actual = $mapping->getExtraColumns('entity_test_revision');
    $this->assertEquals($expected, $actual);
    $actual = $mapping->getExtraColumns('entity_test_field_data');
    $this->assertEquals($expected, $actual);
    $actual = $mapping->getExtraColumns('entity_test_field_revision');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests getTableMapping() with a complex entity type with fields.
   *
   * @param string[] $entity_keys
   *   A map of entity keys to use for the mocked entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   *
   * @dataProvider providerTestGetTableMappingSimple
   */
  public function testGetTableMappingRevisionableTranslatableWithFields(array $entity_keys): void {
    // This allows to re-use the data provider.
    $entity_keys = [
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
      'langcode' => 'langcode',
    ];

    // PHPUnit does not allow for multiple data providers.
    $test_cases = [
      [],
      ['revision_created' => 'revision_timestamp'],
      ['revision_user' => 'revision_uid'],
      ['revision_log_message' => 'revision_log'],
      ['revision_created' => 'revision_timestamp', 'revision_user' => 'revision_uid'],
      ['revision_created' => 'revision_timestamp', 'revision_log_message' => 'revision_log'],
      ['revision_user' => 'revision_uid', 'revision_log_message' => 'revision_log'],
      ['revision_created' => 'revision_timestamp', 'revision_user' => 'revision_uid', 'revision_log_message' => 'revision_log'],
    ];
    foreach ($test_cases as $revision_metadata_field_names) {
      $this->setUp();

      $base_field_names = ['title'];
      $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
      $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);

      $revisionable_field_names = ['description', 'owner'];
      $this->fieldDefinitions += $this->mockFieldDefinitions(array_merge($revisionable_field_names, array_values($revision_metadata_field_names)), ['isRevisionable' => TRUE]);

      $this->entityType->expects($this->atLeastOnce())
        ->method('isRevisionable')
        ->willReturn(TRUE);
      $this->entityType->expects($this->atLeastOnce())
        ->method('isTranslatable')
        ->willReturn(TRUE);
      $this->entityType->expects($this->atLeastOnce())
        ->method('getDataTable')
        ->willReturn('entity_test_field_data');
      $this->entityType->expects($this->any())
        ->method('getKey')
        ->willReturnMap([
          ['id', $entity_keys['id']],
          ['uuid', $entity_keys['uuid']],
          ['bundle', $entity_keys['bundle']],
          ['revision', $entity_keys['revision']],
          ['langcode', $entity_keys['langcode']],
        ]);
      $this->entityType->expects($this->any())
        ->method('getRevisionMetadataKeys')
        ->willReturn($revision_metadata_field_names);

      $this->setUpEntityStorage();

      $mapping = $this->entityStorage->getTableMapping();

      $expected = [
        'entity_test',
        'entity_test_field_data',
        'entity_test_revision',
        'entity_test_field_revision',
      ];
      $this->assertEquals($expected, $mapping->getTableNames());

      $expected = [
        'entity_test',
        'entity_test_field_data',
        'entity_test_revision',
        'entity_test_field_revision',
      ];
      $this->assertEquals($expected, $mapping->getTableNames());

      // The default language code is not stored on the base table.
      $expected = array_values(array_filter([
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['bundle'],
        $entity_keys['uuid'],
        $entity_keys['langcode'],
      ]));
      $actual = $mapping->getFieldNames('entity_test');
      $this->assertEquals($expected, $actual);
      // The revision table on the other hand does not store the bundle and the
      // UUID.
      $expected = array_merge(array_filter([
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['langcode'],
      ]), array_values($revision_metadata_field_names));
      $actual = $mapping->getFieldNames('entity_test_revision');
      $this->assertEquals($expected, $actual);
      // The UUID is not stored on the data table.
      $expected = array_merge(array_filter([
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['bundle'],
        $entity_keys['langcode'],
      ]), $base_field_names, $revisionable_field_names);
      $actual = $mapping->getFieldNames('entity_test_field_data');
      $this->assertEquals($expected, $actual);
      // The data revision also does not store the bundle.
      $expected = array_merge(array_filter([
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['langcode'],
      ]), $revisionable_field_names);
      $actual = $mapping->getFieldNames('entity_test_field_revision');
      $this->assertEquals($expected, $actual);

      $expected = [];
      $actual = $mapping->getExtraColumns('entity_test');
      $this->assertEquals($expected, $actual);
      $actual = $mapping->getExtraColumns('entity_test_revision');
      $this->assertEquals($expected, $actual);
      $actual = $mapping->getExtraColumns('entity_test_field_data');
      $this->assertEquals($expected, $actual);
      $actual = $mapping->getExtraColumns('entity_test_field_revision');
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * @covers ::create
   */
  public function testCreate(): void {
    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');

    $language = new Language(['id' => 'en']);
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language);

    $entity = $this->getMockBuilder(ContentEntityBaseMockableClass::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['id'])
      ->getMock();

    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn($this->entityTypeId);
    $this->entityType->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn(get_class($entity));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getKeys')
      ->willReturn(['id' => 'id']);

    // ContentEntityStorageBase iterates over the entity which calls this method
    // internally in ContentEntityBase::getProperties().
    $this->entityFieldManager
      ->getFieldDefinitions(Argument::type('string'), Argument::type('string'))
      ->willReturn([])
      ->shouldBeCalledOnce();

    $this->entityType->expects($this->atLeastOnce())
      ->method('isRevisionable')
      ->willReturn(FALSE);
    $this->entityTypeManager
      ->getDefinition($this->entityType->id())
      ->willReturn($this->entityType)
      ->shouldBeCalled();

    $this->setUpEntityStorage();

    $entity = $this->entityStorage->create();
    $entity->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('foo');

    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertSame('foo', $entity->id());
    $this->assertTrue($entity->isNew());
  }

  /**
   * Returns a set of mock field definitions for the given names.
   *
   * @param array $field_names
   *   An array of field names.
   * @param array $methods
   *   (optional) An associative array of mock method return values keyed by
   *   method name.
   *
   * @return \Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
   *   An array of mock base field definitions.
   */
  protected function mockFieldDefinitions(array $field_names, $methods = []): array {
    $field_definitions = [];
    $definition = $this->createMock('Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface');

    // Assign common method return values.
    $methods += [
      'isBaseField' => TRUE,
    ];
    foreach ($methods as $method => $result) {
      $definition
        ->expects($this->any())
        ->method($method)
        ->willReturn($result);
    }

    // Assign field names to mock definitions.
    foreach ($field_names as $field_name) {
      $field_definitions[$field_name] = clone $definition;
      $field_definitions[$field_name]
        ->expects($this->any())
        ->method('getName')
        ->willReturn($field_name);
    }

    return $field_definitions;
  }

  /**
   * Sets up the content entity database storage.
   */
  protected function setUpEntityStorage(): void {
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager
      ->getDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $this->entityTypeManager
      ->getActiveDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $this->entityFieldManager
      ->getFieldStorageDefinitions($this->entityType->id())
      ->willReturn($this->fieldDefinitions);

    $this->entityFieldManager
      ->getActiveFieldStorageDefinitions($this->entityType->id())
      ->willReturn($this->fieldDefinitions);

    $this->entityStorage = new SqlContentEntityStorage($this->entityType, $this->connection, $this->entityFieldManager->reveal(), $this->cache, $this->languageManager, new MemoryCache(new Time()), $this->entityTypeBundleInfo, $this->entityTypeManager->reveal());
    $this->entityStorage->setModuleHandler($this->moduleHandler);
  }

  /**
   * @covers ::doLoadMultiple
   * @covers ::buildCacheId
   * @covers ::getFromPersistentCache
   */
  public function testLoadMultiplePersistentCached(): void {
    $this->setUpModuleHandlerNoImplementations();

    $key = 'values:' . $this->entityTypeId . ':1';
    $id = 1;
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTestEntityInterface')
      ->getMockForAbstractClass();
    $entity->expects($this->any())
      ->method('id')
      ->willReturn($id);

    $this->entityType->expects($this->atLeastOnce())
      ->method('isPersistentlyCacheable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn($this->entityTypeId);

    $this->cache->expects($this->once())
      ->method('getMultiple')
      ->with([$key])
      ->willReturn([$key => (object) ['data' => $entity]]);
    $this->cache->expects($this->never())
      ->method('set');

    $this->setUpEntityStorage();
    $entities = $this->entityStorage->loadMultiple([$id]);
    $this->assertEquals($entity, $entities[$id]);
  }

  /**
   * @covers ::doLoadMultiple
   * @covers ::buildCacheId
   * @covers ::getFromPersistentCache
   * @covers ::setPersistentCache
   */
  public function testLoadMultipleNoPersistentCache(): void {
    $this->setUpModuleHandlerNoImplementations();

    $id = 1;
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTestEntityInterface')
      ->getMockForAbstractClass();
    $entity->expects($this->any())
      ->method('id')
      ->willReturn($id);

    $this->entityType->expects($this->any())
      ->method('isPersistentlyCacheable')
      ->willReturn(FALSE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn($this->entityTypeId);

    // There should be no calls to the cache backend for an entity type without
    // persistent caching.
    $this->cache->expects($this->never())
      ->method('getMultiple');
    $this->cache->expects($this->never())
      ->method('set');

    $this->entityTypeManager
      ->getActiveDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $entity_storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->setConstructorArgs([$this->entityType, $this->connection, $this->entityFieldManager->reveal(), $this->cache, $this->languageManager, new MemoryCache(new Time()), $this->entityTypeBundleInfo, $this->entityTypeManager->reveal()])
      ->onlyMethods(['getFromStorage', 'invokeStorageLoadHook', 'initTableLayout'])
      ->getMock();
    $entity_storage->method('invokeStorageLoadHook')
      ->willReturn(NULL);
    $entity_storage->method('initTableLayout')
      ->willReturn(NULL);
    $entity_storage->expects($this->once())
      ->method('getFromStorage')
      ->with([$id])
      ->willReturn([$id => $entity]);

    $entities = $entity_storage->loadMultiple([$id]);
    $this->assertEquals($entity, $entities[$id]);
  }

  /**
   * @covers ::doLoadMultiple
   * @covers ::buildCacheId
   * @covers ::getFromPersistentCache
   * @covers ::setPersistentCache
   */
  public function testLoadMultiplePersistentCacheMiss(): void {
    $this->setUpModuleHandlerNoImplementations();

    $id = 1;
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTestEntityInterface')
      ->getMockForAbstractClass();
    $entity->expects($this->any())
      ->method('id')
      ->willReturn($id);

    $this->entityType->expects($this->any())
      ->method('isPersistentlyCacheable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn($this->entityTypeId);

    // In case of a cache miss, the entity is loaded from the storage and then
    // set in the cache.
    $key = 'values:' . $this->entityTypeId . ':1';
    $this->cache->expects($this->once())
      ->method('getMultiple')
      ->with([$key])
      ->willReturn([]);
    $this->cache->expects($this->once())
      ->method('setMultiple')
      ->with([
        $key => [
          'data' => $entity,
          'tags' => [$this->entityTypeId . '_values', 'entity_field_info'],
        ],
      ]);

    $this->entityTypeManager
      ->getActiveDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $entity_storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->setConstructorArgs([$this->entityType, $this->connection, $this->entityFieldManager->reveal(), $this->cache, $this->languageManager, new MemoryCache(new Time()), $this->entityTypeBundleInfo, $this->entityTypeManager->reveal()])
      ->onlyMethods(['getFromStorage', 'invokeStorageLoadHook', 'initTableLayout'])
      ->getMock();
    $entity_storage->method('invokeStorageLoadHook')
      ->willReturn(NULL);
    $entity_storage->method('initTableLayout')
      ->willReturn(NULL);
    $entity_storage->expects($this->once())
      ->method('getFromStorage')
      ->with([$id])
      ->willReturn([$id => $entity]);

    $entities = $entity_storage->loadMultiple([$id]);
    $this->assertEquals($entity, $entities[$id]);
  }

  /**
   * @covers ::hasData
   */
  public function testHasData(): void {
    $query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects(($this->once()))
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturn($query);
    $query->expects(($this->once()))
      ->method('range')
      ->with(0, 1)
      ->willReturn($query);
    $query->expects(($this->once()))
      ->method('execute')
      ->willReturn([5]);

    $factory = $this->createMock(QueryFactoryInterface::class);
    $factory->expects($this->once())
      ->method('get')
      ->with($this->entityType, 'AND')
      ->willReturn($query);

    $this->container->set('entity.query.sql', $factory);

    $database = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager
      ->getDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $this->entityTypeManager
      ->getActiveDefinition($this->entityType->id())
      ->willReturn($this->entityType);

    $this->entityFieldManager
      ->getFieldStorageDefinitions($this->entityType->id())
      ->willReturn($this->fieldDefinitions);

    $this->entityFieldManager
      ->getActiveFieldStorageDefinitions($this->entityType->id())
      ->willReturn($this->fieldDefinitions);

    $this->entityStorage = new SqlContentEntityStorage($this->entityType, $database, $this->entityFieldManager->reveal(), $this->cache, $this->languageManager, new MemoryCache(new Time()), $this->entityTypeBundleInfo, $this->entityTypeManager->reveal());

    $result = $this->entityStorage->hasData();

    $this->assertTrue($result, 'hasData returned TRUE');
  }

  /**
   * Tests entity ID sanitization.
   */
  public function testCleanIds(): void {
    $valid_ids = [
      -1,
      0,
      1,
      '-1',
      '0',
      '1',
      0123,
      -0x1A,
      0x1AFC,
      -0b111,
      0b101,
      '0123',
      '00123',
      '000123',
      '-0123',
      '-00123',
      '-000123',
      -10.0,
      -1.0,
      0.0,
      1.0,
      10.0,
      -10.00,
      -1.00,
      0.00,
      1.00,
      10.00,
    ];

    $this->fieldDefinitions = $this->mockFieldDefinitions(['id']);
    $this->fieldDefinitions['id']->expects($this->any())
      ->method('getType')
      ->willReturn('integer');

    $this->setUpEntityStorage();

    $this->entityType->expects($this->any())
      ->method('getKey')
      ->willReturnMap([['id', 'id']]);

    $method = new \ReflectionMethod($this->entityStorage, 'cleanIds');
    $this->assertEquals($valid_ids, $method->invoke($this->entityStorage, $valid_ids));

    $invalid_ids = [
      '--1',
      '-0x1A',
      '0x1AFC',
      '-0b111',
      '0b101',
      'a',
      FALSE,
      TRUE,
      NULL,
      '32acb',
      123.123,
      123.678,
    ];
    $this->assertEquals([], $method->invoke($this->entityStorage, $invalid_ids));

  }

  /**
   * Sets up the module handler with no implementations.
   */
  protected function setUpModuleHandlerNoImplementations(): void {
    $this->moduleHandler->expects($this->any())
      ->method('invokeAllWith')
      ->willReturnMap([
        ['entity_load', []],
        [$this->entityTypeId . '_load', []],
      ]);

    $this->container->set('module_handler', $this->moduleHandler);
  }

}

/**
 * Provides an entity with dummy implementations of static methods.
 */
abstract class SqlContentEntityStorageTestEntityInterface implements EntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
  }

}
