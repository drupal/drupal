<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\ContentEntityDatabaseStorageTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the fieldable database storage.
 *
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityDatabaseStorage
 *
 * @group Drupal
 * @group Entity
 */
class ContentEntityDatabaseStorageTest extends UnitTestCase {

  /**
   * The content entity database storage used in this test.
   *
   * @var \Drupal\Core\Entity\ContentEntityDatabaseStorage|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \Drupal\Core\Field\FieldDefinition[]|\PHPUnit_Framework_MockObject_MockObject[]
   */
  protected $fieldDefinitions = array();

  /**
   * The mocked entity manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Fieldable database storage',
      'description' => 'Tests the fieldable database storage enhancer for entities.',
      'group' => 'Entity'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityType = $this->getMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('id')
      ->will($this->returnValue('entity_test'));

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
  }

  /**
   * Tests ContentEntityDatabaseStorage::getBaseTable().
   *
   * @param string $base_table
   *   The base table to be returned by the mocked entity type.
   * @param string $expected
   *   The expected return value of
   *   ContentEntityDatabaseStorage::getBaseTable().
   *
   * @covers ::__construct()
   * @covers ::getBaseTable()
   *
   * @dataProvider providerTestGetBaseTable
   */
  public function testGetBaseTable($base_table, $expected) {
    $this->entityType->expects($this->once())
      ->method('getBaseTable')
      ->will($this->returnValue('entity_test'));

    $this->setUpEntityStorage();

    $this->assertSame($expected, $this->entityStorage->getBaseTable());
  }

  /**
   * Provides test data for testGetBaseTable().
   *
   * @return array[]
   *   An nested array where each inner array has the base table to be returned
   *   by the mocked entity type as the first value and the expected return
   *   value of ContentEntityDatabaseStorage::getBaseTable() as the second
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
   * Tests ContentEntityDatabaseStorage::getRevisionTable().
   *
   * @param string $revision_table
   *   The revision table to be returned by the mocked entity type.
   * @param string $expected
   *   The expected return value of
   *   ContentEntityDatabaseStorage::getRevisionTable().
   *
   * @cover ::__construct()
   * @covers ::getRevisionTable()
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
   *   return value of ContentEntityDatabaseStorage::getRevisionTable() as the
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
   * Tests ContentEntityDatabaseStorage::getDataTable().
   *
   * @cover ::__construct()
   * @covers ::getDataTable()
   */
  public function testGetDataTable() {
    $this->entityType->expects($this->once())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->exactly(2))
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));

    $this->setUpEntityStorage();

    $this->assertSame('entity_test_field_data', $this->entityStorage->getDataTable());
  }

  /**
   * Tests ContentEntityDatabaseStorage::getRevisionDataTable().
   *
   * @param string $revision_data_table
   *   The revision data table to be returned by the mocked entity type.
   * @param string $expected
   *   The expected return value of
   *   ContentEntityDatabaseStorage::getRevisionDataTable().
   *
   * @cover ::__construct()
   * @covers ::getRevisionDataTable()
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
    $this->entityType->expects($this->exactly(2))
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
   *   return value of ContentEntityDatabaseStorage::getRevisionDataTable() as
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
   * Tests ContentEntityDatabaseStorage::getSchema().
   *
   * @covers ::__construct()
   * @covers ::getSchema()
   * @covers ::schemaHandler()
   * @covers ::getTableMapping()
   */
  public function testGetSchema() {
    $columns = array(
      'value' => array(
        'type' => 'int',
      ),
    );

    $this->fieldDefinitions['id'] = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $this->fieldDefinitions['id']->expects($this->once())
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
        // ContentEntitySchemaHandler::initializeBaseTable()
        array('id' => 'id'),
        // ContentEntitySchemaHandler::processBaseTable()
        array('id' => 'id'),
      )));

    $this->entityManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with($this->entityType->id())
      ->will($this->returnValue($this->fieldDefinitions));

    $this->setUpEntityStorage();

    $expected = array(
      'entity_test' => array(
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
      ),
    );
    $this->assertEquals($expected, $this->entityStorage->getSchema());

    // Test that repeated calls do not result in repeatedly instantiating
    // ContentEntitySchemaHandler as getFieldStorageDefinitions() is only
    // expected to be called once.
    $this->assertEquals($expected, $this->entityStorage->getSchema());
  }

  /**
   * Tests getTableMapping() with an empty entity type.
   *
   * @covers ::__construct()
   * @covers ::getTableMapping()
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
   * @covers ::__construct()
   * @covers ::getTableMapping()
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
   * @covers ::__construct()
   * @covers ::getTableMapping()
   *
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingSimpleWithFields(array $entity_keys) {
    $base_field_names = array('title', 'description', 'owner');
    $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);

    $definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $this->fieldDefinitions = array_fill_keys($field_names, $definition);

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
   * @covers ::__construct()
   * @covers ::getTableMapping()
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
   * @covers ::__construct()
   * @covers ::getTableMapping()
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

      $definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
      $this->fieldDefinitions = array_fill_keys($field_names, $definition);

      $revisionable_field_names = array('description', 'owner');
      $definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
      // isRevisionable() is only called once, but we re-use the same definition
      // for all revisionable fields.
      $definition->expects($this->any())
        ->method('isRevisionable')
        ->will($this->returnValue(TRUE));
      $field_names = array_merge(
        $field_names,
        $revisionable_field_names
      );
      $this->fieldDefinitions += array_fill_keys(
        array_merge($revisionable_field_names, $revision_metadata_field_names),
        $definition
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
   * @covers ::__construct()
   * @covers ::getTableMapping()
   *
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingTranslatable(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys['langcode'] = 'langcode';

    $this->entityType->expects($this->exactly(2))
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->exactly(3))
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', $entity_keys['id']),
        array('uuid', $entity_keys['uuid']),
        array('bundle', $entity_keys['bundle']),
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
   * @covers ::__construct()
   * @covers ::getTableMapping()
   *
   * @dataProvider providerTestGetTableMappingSimple()
   */
  public function testGetTableMappingTranslatableWithFields(array $entity_keys) {
    // This allows to re-use the data provider.
    $entity_keys['langcode'] = 'langcode';

    $base_field_names = array('title', 'description', 'owner');
    $field_names = array_merge(array_values(array_filter($entity_keys)), $base_field_names);

    $definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $this->fieldDefinitions = array_fill_keys($field_names, $definition);

    $this->entityType->expects($this->exactly(2))
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->exactly(3))
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', $entity_keys['id']),
        array('uuid', $entity_keys['uuid']),
        array('bundle', $entity_keys['bundle']),
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
   * @covers ::__construct()
   * @covers ::getTableMapping()
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

    $this->entityType->expects($this->exactly(2))
      ->method('isRevisionable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->exactly(2))
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->exactly(3))
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
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
   * @covers ::__construct()
   * @covers ::getTableMapping()
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

      $definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
      $this->fieldDefinitions = array_fill_keys($field_names, $definition);

      $revisionable_field_names = array('description', 'owner');
      $definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
      // isRevisionable() is only called once, but we re-use the same definition
      // for all revisionable fields.
      $definition->expects($this->any())
        ->method('isRevisionable')
        ->will($this->returnValue(TRUE));
      $this->fieldDefinitions += array_fill_keys(
        array_merge($revisionable_field_names, $revision_metadata_field_names),
        $definition
      );

      $this->entityType->expects($this->exactly(2))
        ->method('isRevisionable')
        ->will($this->returnValue(TRUE));
      $this->entityType->expects($this->exactly(2))
        ->method('isTranslatable')
        ->will($this->returnValue(TRUE));
      $this->entityType->expects($this->exactly(3))
        ->method('getDataTable')
        ->will($this->returnValue('entity_test_field_data'));
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
   * Tests field SQL schema generation for an entity with a string identifier.
   *
   * @covers ::_fieldSqlSchema()
   */
  public function testFieldSqlSchemaForEntityWithStringIdentifier() {
    $field_type_manager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    $container->set('entity.manager', $this->entityManager);
    \Drupal::setContainer($container);

    $this->entityType->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', 'id'),
        array('revision', 'revision'),
      )));
    $this->entityType->expects($this->once())
      ->method('hasKey')
      ->with('revision')
      ->will($this->returnValue(TRUE));

    $field_type_manager->expects($this->exactly(2))
      ->method('getDefaultSettings')
      ->will($this->returnValue(array()));
    $field_type_manager->expects($this->exactly(2))
      ->method('getDefaultInstanceSettings')
      ->will($this->returnValue(array()));

    $this->fieldDefinitions['id'] = FieldDefinition::create('string')
      ->setName('id');
    $this->fieldDefinitions['revision'] = FieldDefinition::create('string')
      ->setName('revision');

    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity')
      ->will($this->returnValue($this->entityType));
    $this->entityManager->expects($this->once())
      ->method('getBaseFieldDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    // Define a field definition for a test_field field.
    $field = $this->getMock('\Drupal\Core\Field\FieldStorageDefinitionInterface');
    $field->deleted = FALSE;

    $field->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('test_field'));

    $field->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->will($this->returnValue('test_entity'));

    $field_schema = array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 10,
          'not null' => FALSE,
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(),
      'foreign keys' => array(),
    );
    $field->expects($this->any())
      ->method('getSchema')
      ->will($this->returnValue($field_schema));

    $schema = ContentEntityDatabaseStorage::_fieldSqlSchema($field);

    // Make sure that the entity_id schema field if of type varchar.
    $this->assertEquals($schema['test_entity__test_field']['fields']['entity_id']['type'], 'varchar');
    $this->assertEquals($schema['test_entity__test_field']['fields']['revision_id']['type'], 'varchar');
  }

  /**
   * @covers ::create()
   */
  public function testCreate() {
    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);
    $container->set('entity.manager', $this->entityManager);
    $container->set('module_handler', $module_handler);
    \Drupal::setContainer($container);

    $entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->setMethods(array('id'))
      ->getMockForAbstractClass();

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
   * Sets up the content entity database storage.
   */
  protected function setUpEntityStorage() {
    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityManager->expects($this->any())
      ->method('getBaseFieldDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    $this->entityStorage = new ContentEntityDatabaseStorage($this->entityType, $connection, $this->entityManager);
  }

}
