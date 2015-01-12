<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTest.
 */

namespace Drupal\Tests\Core\Entity\Sql;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Sql\SqlContentEntityStorage
 * @group Entity
 */
class SqlContentEntityStorageTest extends UnitTestCase {

  /**
   * The content entity database storage used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorage;

  /**
   * The mocked entity type used in this test.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * An array of field definitions used for this test, keyed by field name.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]|\PHPUnit_Framework_MockObject_MockObject[]
   */
  protected $fieldDefinitions = array();

  /**
   * The mocked entity manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

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
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityType = $this->getMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->will($this->returnValue(new Language(array('langcode' => 'en'))));
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
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
  public function testGetBaseTable($base_table, $expected) {
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
   *   An nested array where each inner array has the base table to be returned
   *   by the mocked entity type as the first value and the expected return
   *   value of SqlContentEntityStorage::getBaseTable() as the second
   *   value.
   */
  public function providerTestGetBaseTable() {
    return array(
      // Test that the entity type's base table is used, if provided.
      array('entity_test', 'entity_test'),
      // Test that the storage falls back to the entity type ID.
      array(NULL, 'entity_test'),
    );
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
  public function testGetRevisionTable($revision_table, $expected) {
    $this->entityType->expects($this->once())
      ->method('isRevisionable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->once())
      ->method('getRevisionTable')
      ->will($this->returnValue($revision_table));

    $this->setUpEntityStorage();

    $this->assertSame($expected, $this->entityStorage->getRevisionTable());
  }

  /**
   * Provides test data for testGetRevisionTable().
   *
   * @return array[]
   *   An nested array where each inner array has the revision table to be
   *   returned by the mocked entity type as the first value and the expected
   *   return value of SqlContentEntityStorage::getRevisionTable() as the
   *   second value.
   */
  public function providerTestGetRevisionTable() {
    return array(
      // Test that the entity type's revision table is used, if provided.
      array('entity_test_revision', 'entity_test_revision'),
      // Test that the storage falls back to the entity type ID with a
      // '_revision' suffix.
      array(NULL, 'entity_test_revision'),
    );
  }

  /**
   * Tests SqlContentEntityStorage::getDataTable().
   *
   * @covers ::__construct
   * @covers ::getDataTable
   */
  public function testGetDataTable() {
    $this->entityType->expects($this->once())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->exactly(1))
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));

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
  public function testGetRevisionDataTable($revision_data_table, $expected) {
    $this->entityType->expects($this->once())
      ->method('isRevisionable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->once())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->exactly(1))
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->entityType->expects($this->once())
      ->method('getRevisionDataTable')
      ->will($this->returnValue($revision_data_table));

    $this->setUpEntityStorage();

    $actual = $this->entityStorage->getRevisionDataTable();
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides test data for testGetRevisionDataTable().
   *
   * @return array[]
   *   An nested array where each inner array has the revision data table to be
   *   returned by the mocked entity type as the first value and the expected
   *   return value of SqlContentEntityStorage::getRevisionDataTable() as
   *   the second value.
   */
  public function providerTestGetRevisionDataTable() {
    return array(
      // Test that the entity type's revision data table is used, if provided.
      array('entity_test_field_revision', 'entity_test_field_revision'),
      // Test that the storage falls back to the entity type ID with a
      // '_field_revision' suffix.
      array(NULL, 'entity_test_field_revision'),
    );
  }

  /**
   * Tests ContentEntityDatabaseStorage::onEntityTypeCreate().
   *
   * @covers ::__construct
   * @covers ::onEntityTypeCreate
   * @covers ::getTableMapping
   */
  public function testOnEntityTypeCreate() {
    $columns = array(
      'value' => array(
        'type' => 'int',
      ),
    );

    $this->fieldDefinitions = $this->mockFieldDefinitions(array('id'));
    $this->fieldDefinitions['id']->expects($this->any())
      ->method('getColumns')
      ->will($this->returnValue($columns));
    $this->fieldDefinitions['id']->expects($this->once())
      ->method('getSchema')
      ->will($this->returnValue(array('columns' => $columns)));

    $this->entityType->expects($this->once())
      ->method('getKeys')
      ->will($this->returnValue(array('id' => 'id')));
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        // EntityStorageBase::__construct()
        array('id', 'id'),
        // ContentEntityStorageBase::__construct()
        array('uuid', NULL),
        array('bundle', NULL),
        // SqlContentEntityStorageSchema::initializeBaseTable()
        array('id' => 'id'),
        // SqlContentEntityStorageSchema::processBaseTable()
        array('id' => 'id'),
      )));

    $this->setUpEntityStorage();

    $expected = array(
      'description' => 'The base table for entity_test entities.',
      'fields' => array(
        'id' => array(
          'type' => 'serial',
          'description' => NULL,
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('id'),
      'unique keys' => array(),
      'indexes' => array(),
      'foreign keys' => array(),
    );

    $schema_handler = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();
    $schema_handler->expects($this->any())
      ->method('createTable')
      ->with($this->equalTo('entity_test'), $this->equalTo($expected));

    $this->connection->expects($this->once())
      ->method('schema')
      ->will($this->returnValue($schema_handler));

    $storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->setConstructorArgs(array($this->entityType, $this->connection, $this->entityManager, $this->cache, $this->languageManager))
      ->setMethods(array('getStorageSchema'))
      ->getMock();

    $key_value = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreInterface');
    $schema_handler = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema')
      ->setConstructorArgs(array($this->entityManager, $this->entityType, $storage, $this->connection))
      ->setMethods(array('installedStorageSchema', 'createSharedTableSchema'))
      ->getMock();
    $schema_handler
      ->expects($this->any())
      ->method('installedStorageSchema')
      ->will($this->returnValue($key_value));

    $storage
      ->expects($this->any())
      ->method('getStorageSchema')
      ->will($this->returnValue($schema_handler));

    $storage->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests getTableMapping() with an empty entity type.
   *
   * @covers ::__construct
   * @covers ::getTableMapping
   */
  public function testGetTableMappingEmpty() {
    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();
    $this->assertSame(array('entity_test'), $mapping->getTableNames());
    $this->assertSame(array(), $mapping->getFieldNames('entity_test'));
    $this->assertSame(array(), $mapping->getExtraColumns('entity_test'));
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingSimple(array $entity_keys) {
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', $entity_keys['id']),
        array('uuid', $entity_keys['uuid']),
        array('bundle', $entity_keys['bundle']),
      )));

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $this->assertEquals(array('entity_test'), $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $this->assertEquals($expected, $mapping->getFieldNames('entity_test'));

    $this->assertEquals(array(), $mapping->getExtraColumns('entity_test'));
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingSimpleWithFields(array $entity_keys) {
    $base_field_names = array('title', 'description', 'owner');
    $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
    $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);
    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();
    $this->assertEquals(array('entity_test'), $mapping->getTableNames());
    $this->assertEquals($field_names, $mapping->getFieldNames('entity_test'));
    $this->assertEquals(array(), $mapping->getExtraColumns('entity_test'));
  }

  /**
   * Provides test data for testGetTableMappingSimple().
   *
   * @return array[]
   *   A nested array, where each inner array has a single value being a  map of
   *   entity keys to use for the mocked entity type.
   */
  public function providerTestGetTableMappingSimple() {
    return array(
      array(array(
        'id' => 'test_id',
        'bundle' => NULL,
        'uuid' => NULL,
      )),
      array(array(
        'id' => 'test_id',
        'bundle' => 'test_bundle',
        'uuid' => NULL,
      )),
      array(array(
        'id' => 'test_id',
        'bundle' => NULL,
        'uuid' => 'test_uuid',
      )),
      array(array(
        'id' => 'test_id',
        'bundle' => 'test_bundle',
        'uuid' => 'test_uuid',
      )),
    );
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingRevisionable(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys = array(
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
    );

    $this->entityType->expects($this->exactly(2))
      ->method('isRevisionable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', $entity_keys['id']),
        array('uuid', $entity_keys['uuid']),
        array('bundle', $entity_keys['bundle']),
        array('revision', $entity_keys['revision']),
      )));

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = array('entity_test', 'entity_test_revision');
    $this->assertEquals($expected, $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $this->assertEquals($expected, $mapping->getFieldNames('entity_test'));
    $expected = array($entity_keys['id'], $entity_keys['revision']);
    $this->assertEquals($expected, $mapping->getFieldNames('entity_test_revision'));

    $this->assertEquals(array(), $mapping->getExtraColumns('entity_test'));
    $this->assertEquals(array(), $mapping->getExtraColumns('entity_test_revision'));
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingRevisionableWithFields(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys = array(
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
    );

    // PHPUnit does not allow for multiple data providers.
    $test_cases = array(
      array(),
      array('revision_timestamp'),
      array('revision_uid'),
      array('revision_log'),
      array('revision_timestamp', 'revision_uid'),
      array('revision_timestamp', 'revision_log'),
      array('revision_uid', 'revision_log'),
      array('revision_timestamp', 'revision_uid', 'revision_log'),
    );
    foreach ($test_cases as $revision_metadata_field_names) {
      $this->setUp();

      $base_field_names = array('title');
      $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
      $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);

      $revisionable_field_names = array('description', 'owner');
      $field_names = array_merge($field_names, $revisionable_field_names);
      $this->fieldDefinitions += $this->mockFieldDefinitions(array_merge($revisionable_field_names, $revision_metadata_field_names), array('isRevisionable' => TRUE));

      $this->entityType->expects($this->exactly(2))
        ->method('isRevisionable')
        ->will($this->returnValue(TRUE));
      $this->entityType->expects($this->any())
        ->method('getKey')
        ->will($this->returnValueMap(array(
          array('id', $entity_keys['id']),
          array('uuid', $entity_keys['uuid']),
          array('bundle', $entity_keys['bundle']),
          array('revision', $entity_keys['revision']),
        )));

      $this->setUpEntityStorage();

      $mapping = $this->entityStorage->getTableMapping();

      $expected = array('entity_test', 'entity_test_revision');
      $this->assertEquals($expected, $mapping->getTableNames());

      $this->assertEquals($field_names, $mapping->getFieldNames('entity_test'));
      $expected = array_merge(
        array($entity_keys['id'], $entity_keys['revision']),
        $revisionable_field_names,
        $revision_metadata_field_names
      );
      $this->assertEquals($expected, $mapping->getFieldNames('entity_test_revision'));

      $this->assertEquals(array(), $mapping->getExtraColumns('entity_test'));
      $this->assertEquals(array(), $mapping->getExtraColumns('entity_test_revision'));
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingTranslatable(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys['langcode'] = 'langcode';

    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', $entity_keys['id']),
        array('uuid', $entity_keys['uuid']),
        array('bundle', $entity_keys['bundle']),
        array('langcode', $entity_keys['langcode']),
      )));

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = array('entity_test', 'entity_test_field_data');
    $this->assertEquals($expected, $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $actual = $mapping->getFieldNames('entity_test');
    $this->assertEquals($expected, $actual);
    // The UUID is not stored on the data table.
    $expected = array_values(array_filter(array(
      $entity_keys['id'],
      $entity_keys['bundle'],
      $entity_keys['langcode'],
    )));
    $actual = $mapping->getFieldNames('entity_test_field_data');
    $this->assertEquals($expected, $actual);

    $expected = array();
    $actual = $mapping->getExtraColumns('entity_test');
    $this->assertEquals($expected, $actual);
    $expected = array('default_langcode');
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingTranslatableWithFields(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys['langcode'] = 'langcode';

    $base_field_names = array('title', 'description', 'owner');
    $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
    $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);

    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', $entity_keys['id']),
        array('uuid', $entity_keys['uuid']),
        array('bundle', $entity_keys['bundle']),
        array('langcode', $entity_keys['langcode']),
      )));

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = array('entity_test', 'entity_test_field_data');
    $this->assertEquals($expected, $mapping->getTableNames());

    $expected = array_values(array_filter($entity_keys));
    $actual = $mapping->getFieldNames('entity_test');
    $this->assertEquals($expected, $actual);
    // The UUID is not stored on the data table.
    $expected = array_merge(array_filter(array(
      $entity_keys['id'],
      $entity_keys['bundle'],
      $entity_keys['langcode'],
    )), $base_field_names);
    $actual = $mapping->getFieldNames('entity_test_field_data');
    $this->assertEquals($expected, $actual);

    $expected = array();
    $actual = $mapping->getExtraColumns('entity_test');
    $this->assertEquals($expected, $actual);
    $expected = array('default_langcode');
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingRevisionableTranslatable(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys = array(
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
      'langcode' => 'langcode',
    );

    $this->entityType->expects($this->atLeastOnce())
      ->method('isRevisionable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', $entity_keys['id']),
        array('uuid', $entity_keys['uuid']),
        array('bundle', $entity_keys['bundle']),
        array('revision', $entity_keys['revision']),
        array('langcode', $entity_keys['langcode']),
      )));

    $this->setUpEntityStorage();

    $mapping = $this->entityStorage->getTableMapping();

    $expected = array(
      'entity_test',
      'entity_test_field_data',
      'entity_test_revision',
      'entity_test_field_revision',
    );
    $this->assertEquals($expected, $mapping->getTableNames());

    // The language code is not stored on the base table, but on the revision
    // table.
    $expected = array_values(array_filter(array(
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['bundle'],
      $entity_keys['uuid'],
    )));
    $actual = $mapping->getFieldNames('entity_test');
    $this->assertEquals($expected, $actual);
    // The revision table on the other hand does not store the bundle and the
    // UUID.
    $expected = array_values(array_filter(array(
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['langcode'],
    )));
    $actual = $mapping->getFieldNames('entity_test_revision');
    $this->assertEquals($expected, $actual);
    // The UUID is not stored on the data table.
    $expected = array_values(array_filter(array(
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['bundle'],
      $entity_keys['langcode'],
    )));
    $actual = $mapping->getFieldNames('entity_test_field_data');
    $this->assertEquals($expected, $actual);
    // The data revision also does not store the bundle.
    $expected = array_values(array_filter(array(
      $entity_keys['id'],
      $entity_keys['revision'],
      $entity_keys['langcode'],
    )));
    $actual = $mapping->getFieldNames('entity_test_field_revision');
    $this->assertEquals($expected, $actual);

    $expected = array();
    $actual = $mapping->getExtraColumns('entity_test');
    $this->assertEquals($expected, $actual);
    $actual = $mapping->getExtraColumns('entity_test_revision');
    $this->assertEquals($expected, $actual);
    $expected = array('default_langcode');
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
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingRevisionableTranslatableWithFields(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys = array(
      'id' => $entity_keys['id'],
      'revision' => 'test_revision',
      'bundle' => $entity_keys['bundle'],
      'uuid' => $entity_keys['uuid'],
      'langcode' => 'langcode',
    );

    // PHPUnit does not allow for multiple data providers.
    $test_cases = array(
      array(),
      array('revision_timestamp'),
      array('revision_uid'),
      array('revision_log'),
      array('revision_timestamp', 'revision_uid'),
      array('revision_timestamp', 'revision_log'),
      array('revision_uid', 'revision_log'),
      array('revision_timestamp', 'revision_uid', 'revision_log'),
    );
    foreach ($test_cases as $revision_metadata_field_names) {
      $this->setUp();

      $base_field_names = array('title');
      $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);
      $this->fieldDefinitions = $this->mockFieldDefinitions($field_names);

      $revisionable_field_names = array('description', 'owner');
      $this->fieldDefinitions += $this->mockFieldDefinitions(array_merge($revisionable_field_names, $revision_metadata_field_names), array('isRevisionable' => TRUE));

      $this->entityType->expects($this->atLeastOnce())
        ->method('isRevisionable')
        ->will($this->returnValue(TRUE));
      $this->entityType->expects($this->atLeastOnce())
        ->method('isTranslatable')
        ->will($this->returnValue(TRUE));
      $this->entityType->expects($this->atLeastOnce())
        ->method('getDataTable')
        ->will($this->returnValue('entity_test_field_data'));
      $this->entityType->expects($this->any())
        ->method('getKey')
        ->will($this->returnValueMap(array(
          array('id', $entity_keys['id']),
          array('uuid', $entity_keys['uuid']),
          array('bundle', $entity_keys['bundle']),
          array('revision', $entity_keys['revision']),
          array('langcode', $entity_keys['langcode']),
        )));

      $this->setUpEntityStorage();

      $mapping = $this->entityStorage->getTableMapping();

      $expected = array(
        'entity_test',
        'entity_test_field_data',
        'entity_test_revision',
        'entity_test_field_revision',
      );
      $this->assertEquals($expected, $mapping->getTableNames());

      $expected = array(
        'entity_test',
        'entity_test_field_data',
        'entity_test_revision',
        'entity_test_field_revision',
      );
      $this->assertEquals($expected, $mapping->getTableNames());

      // The language code is not stored on the base table, but on the revision
      // table.
      $expected = array_values(array_filter(array(
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['bundle'],
        $entity_keys['uuid'],
      )));
      $actual = $mapping->getFieldNames('entity_test');
      $this->assertEquals($expected, $actual);
      // The revision table on the other hand does not store the bundle and the
      // UUID.
      $expected = array_merge(array_filter(array(
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['langcode'],
      )), $revision_metadata_field_names);
      $actual = $mapping->getFieldNames('entity_test_revision');
      $this->assertEquals($expected, $actual);
      // The UUID is not stored on the data table.
      $expected = array_merge(array_filter(array(
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['bundle'],
        $entity_keys['langcode'],
      )), $base_field_names, $revisionable_field_names);
      $actual = $mapping->getFieldNames('entity_test_field_data');
      $this->assertEquals($expected, $actual);
      // The data revision also does not store the bundle.
      $expected = array_merge(array_filter(array(
        $entity_keys['id'],
        $entity_keys['revision'],
        $entity_keys['langcode'],
      )), $revisionable_field_names);
      $actual = $mapping->getFieldNames('entity_test_field_revision');
      $this->assertEquals($expected, $actual);

      $expected = array();
      $actual = $mapping->getExtraColumns('entity_test');
      $this->assertEquals($expected, $actual);
      $actual = $mapping->getExtraColumns('entity_test_revision');
      $this->assertEquals($expected, $actual);
      $expected = array('default_langcode');
      $actual = $mapping->getExtraColumns('entity_test_field_data');
      $this->assertEquals($expected, $actual);
      $actual = $mapping->getExtraColumns('entity_test_field_revision');
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * @covers ::create
   */
  public function testCreate() {
    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $language = new Language(array('id' => 'en'));
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));

    $this->container->set('language_manager', $language_manager);
    $this->container->set('entity.manager', $this->entityManager);
    $this->container->set('module_handler', $this->moduleHandler);

    $entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->setMethods(array('id'))
      ->getMockForAbstractClass();

    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getClass')
      ->will($this->returnValue(get_class($entity)));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('id' => 'id')));

    // ContentEntityStorageBase iterates over the entity which calls this method
    // internally in ContentEntityBase::getProperties().
    $this->entityManager->expects($this->once())
      ->method('getFieldDefinitions')
      ->will($this->returnValue(array()));

    $this->entityType->expects($this->atLeastOnce())
      ->method('isRevisionable')
      ->will($this->returnValue(FALSE));
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getDefinition')
      ->with($this->entityType->id())
      ->will($this->returnValue($this->entityType));

    $this->setUpEntityStorage();

    $entity = $this->entityStorage->create();
    $entity->expects($this->atLeastOnce())
      ->method('id')
      ->will($this->returnValue('foo'));

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
   * @return \Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
   *   An array of mock base field definitions.
   */
  protected function mockFieldDefinitions(array $field_names, $methods = array()) {
    $field_definitions = array();
    $definition = $this->getMock('Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface');

    // Assign common method return values.
    $methods += array(
      'isBaseField' => TRUE,
    );
    foreach ($methods as $method => $result) {
      $definition
        ->expects($this->any())
        ->method($method)
        ->will($this->returnValue($result));
    }

    // Assign field names to mock definitions.
    foreach ($field_names as $field_name) {
      $field_definitions[$field_name] = clone $definition;
      $field_definitions[$field_name]
        ->expects($this->any())
        ->method('getName')
        ->will($this->returnValue($field_name));
    }

    return $field_definitions;
  }

  /**
   * Sets up the content entity database storage.
   */
  protected function setUpEntityStorage() {
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($this->entityType));

    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    $this->entityManager->expects($this->any())
      ->method('getBaseFieldDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    $this->entityStorage = new SqlContentEntityStorage($this->entityType, $this->connection, $this->entityManager, $this->cache, $this->languageManager);
  }

  /**
   * @covers ::doLoadMultiple
   * @covers ::buildCacheId
   * @covers ::getFromPersistentCache
   */
  public function testLoadMultiplePersistentCached() {
    $this->setUpModuleHandlerNoImplementations();

    $key = 'values:' . $this->entityTypeId . ':1';
    $id = 1;
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTestEntityInterface')
      ->getMockForAbstractClass();
    $entity->expects($this->any())
      ->method('id')
      ->will($this->returnValue($id));

    $this->entityType->expects($this->atLeastOnce())
      ->method('isPersistentlyCacheable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getClass')
      ->will($this->returnValue(get_class($entity)));

    $this->cache->expects($this->once())
      ->method('getMultiple')
      ->with(array($key))
      ->will($this->returnValue(array($key => (object) array(
          'data' => $entity,
        ))));
    $this->cache->expects($this->never())
      ->method('set');

    $this->setUpEntityStorage();
    $entities = $this->entityStorage->loadMultiple(array($id));
    $this->assertEquals($entity, $entities[$id]);
  }

  /**
   * @covers ::doLoadMultiple
   * @covers ::buildCacheId
   * @covers ::getFromPersistentCache
   * @covers ::setPersistentCache
   */
  public function testLoadMultipleNoPersistentCache() {
    $this->setUpModuleHandlerNoImplementations();

    $id = 1;
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTestEntityInterface')
      ->getMockForAbstractClass();
    $entity->expects($this->any())
      ->method('id')
      ->will($this->returnValue($id));

    $this->entityType->expects($this->any())
      ->method('isPersistentlyCacheable')
      ->will($this->returnValue(FALSE));
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getClass')
      ->will($this->returnValue(get_class($entity)));

    // There should be no calls to the cache backend for an entity type without
    // persistent caching.
    $this->cache->expects($this->never())
      ->method('getMultiple');
    $this->cache->expects($this->never())
      ->method('set');

    $entity_storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->setConstructorArgs(array($this->entityType, $this->connection, $this->entityManager, $this->cache, $this->languageManager))
      ->setMethods(array('getFromStorage'))
      ->getMock();
    $entity_storage->expects($this->once())
      ->method('getFromStorage')
      ->with(array($id))
      ->will($this->returnValue(array($id => $entity)));

    $entities = $entity_storage->loadMultiple(array($id));
    $this->assertEquals($entity, $entities[$id]);
  }

  /**
   * @covers ::doLoadMultiple
   * @covers ::buildCacheId
   * @covers ::getFromPersistentCache
   * @covers ::setPersistentCache
   */
  public function testLoadMultiplePersistentCacheMiss() {
    $this->setUpModuleHandlerNoImplementations();

    $id = 1;
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageTestEntityInterface')
      ->getMockForAbstractClass();
    $entity->expects($this->any())
      ->method('id')
      ->will($this->returnValue($id));

    $this->entityType->expects($this->any())
      ->method('isPersistentlyCacheable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));
    $this->entityType->expects($this->atLeastOnce())
      ->method('getClass')
      ->will($this->returnValue(get_class($entity)));

    // In case of a cache miss, the entity is loaded from the storage and then
    // set in the cache.
    $key = 'values:' . $this->entityTypeId . ':1';
    $this->cache->expects($this->once())
      ->method('getMultiple')
      ->with(array($key))
      ->will($this->returnValue(array()));
    $this->cache->expects($this->once())
      ->method('set')
      ->with($key, $entity, CacheBackendInterface::CACHE_PERMANENT, array($this->entityTypeId . '_values', 'entity_field_info'));

    $entity_storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->setConstructorArgs(array($this->entityType, $this->connection, $this->entityManager, $this->cache, $this->languageManager))
      ->setMethods(array('getFromStorage'))
      ->getMock();
    $entity_storage->expects($this->once())
      ->method('getFromStorage')
      ->with(array($id))
      ->will($this->returnValue(array($id => $entity)));

    $entities = $entity_storage->loadMultiple(array($id));
    $this->assertEquals($entity, $entities[$id]);
  }

  /**
   * @covers ::hasData
   */
  public function testHasData() {
    $query = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
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
      ->willReturn(array(5));

    $factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $factory->expects($this->once())
      ->method('get')
      ->with($this->entityType, 'AND')
      ->willReturn($query);

    $this->container->set('entity.query.sql', $factory);

    $database = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($this->entityType));

    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    $this->entityManager->expects($this->any())
      ->method('getBaseFieldDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    $this->entityStorage = new SqlContentEntityStorage($this->entityType, $database, $this->entityManager, $this->cache, $this->languageManager);

    $result = $this->entityStorage->hasData();

    $this->assertTrue($result, 'hasData returned TRUE');
  }

  /**
   * Tests entity ID sanitization.
   */
  public function testCleanIds() {
    $valid_ids = array(
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
    );

    $this->fieldDefinitions = $this->mockFieldDefinitions(array('id'));
    $this->fieldDefinitions['id']->expects($this->any())
    ->method('getType')
    ->will($this->returnValue('integer'));

    $this->setUpEntityStorage();

    $this->entityType->expects($this->any())
    ->method('getKey')
    ->will($this->returnValueMap(array(
      array('id', 'id'),
    )));

    $method = new \ReflectionMethod($this->entityStorage, 'cleanIds');
    $method->setAccessible(TRUE);
    $this->assertEquals($valid_ids, $method->invoke($this->entityStorage, $valid_ids));

    $invalid_ids = array(
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
    );
    $this->assertEquals(array(), $method->invoke($this->entityStorage, $invalid_ids));

  }

  /**
   * Sets up the module handler with no implementations.
   */
  protected function setUpModuleHandlerNoImplementations() {
    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValueMap(array(
        array('entity_load', array()),
        array($this->entityTypeId . '_load', array())
      )));

    $this->container->set('module_handler', $this->moduleHandler);
  }

}

/**
 * Provides an entity with dummy implementations of static methods, because
 * those cannot be mocked.
 */
abstract class SqlContentEntityStorageTestEntityInterface implements EntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
  }

}
