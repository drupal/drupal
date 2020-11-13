<?php

namespace Drupal\Tests\Core\Entity\Sql;

use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Sql\DefaultTableMapping
 * @group Entity
 */
class DefaultTableMappingTest extends UnitTestCase {

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityType = $this->createMock('\Drupal\Core\Entity\ContentEntityTypeInterface');
    $this->entityType
      ->expects($this->any())
      ->method('id')
      ->willReturn('entity_test');
  }

  /**
   * Tests DefaultTableMapping::getTableNames().
   *
   * @covers ::getTableNames
   */
  public function testGetTableNames() {
    // The storage definitions are only used in getColumnNames() so we do not
    // need to provide any here.
    $table_mapping = new TestDefaultTableMapping($this->entityType, []);
    $this->assertSame([], $table_mapping->getTableNames());

    $table_mapping->setFieldNames('foo', []);
    $this->assertSame(['foo'], $table_mapping->getTableNames());

    $table_mapping->setFieldNames('bar', []);
    $this->assertSame(['foo', 'bar'], $table_mapping->getTableNames());

    $table_mapping->setExtraColumns('baz', []);
    $this->assertSame(['foo', 'bar', 'baz'], $table_mapping->getTableNames());

    // Test that table names are not duplicated.
    $table_mapping->setExtraColumns('foo', []);
    $this->assertSame(['foo', 'bar', 'baz'], $table_mapping->getTableNames());
  }

  /**
   * Tests DefaultTableMapping::getAllColumns().
   *
   * @covers ::__construct
   * @covers ::getAllColumns
   * @covers ::getFieldNames
   * @covers ::getColumnNames
   * @covers ::setFieldNames
   * @covers ::getExtraColumns
   * @covers ::setExtraColumns
   */
  public function testGetAllColumns() {
    // Set up single-column and multi-column definitions.
    $definitions['id'] = $this->setUpDefinition('id', ['value']);
    $definitions['name'] = $this->setUpDefinition('name', ['value']);
    $definitions['type'] = $this->setUpDefinition('type', ['value']);
    $definitions['description'] = $this->setUpDefinition('description', ['value', 'format']);
    $definitions['owner'] = $this->setUpDefinition('owner', [
      'target_id',
      'target_revision_id',
    ]);

    $table_mapping = new TestDefaultTableMapping($this->entityType, $definitions);
    $expected = [];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    // Test adding field columns.
    $table_mapping->setFieldNames('test', ['id']);
    $expected = ['id'];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    $table_mapping->setFieldNames('test', ['id', 'name']);
    $expected = ['id', 'name'];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    $table_mapping->setFieldNames('test', ['id', 'name', 'type']);
    $expected = ['id', 'name', 'type'];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    $table_mapping->setFieldNames('test', [
      'id',
      'name',
      'type',
      'description',
    ]);
    $expected = [
      'id',
      'name',
      'type',
      'description__value',
      'description__format',
    ];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    $table_mapping->setFieldNames('test', [
      'id',
      'name',
      'type',
      'description',
      'owner',
    ]);
    $expected = [
      'id',
      'name',
      'type',
      'description__value',
      'description__format',
      'owner__target_id',
      'owner__target_revision_id',
    ];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    // Test adding extra columns.
    $table_mapping->setFieldNames('test', []);
    $table_mapping->setExtraColumns('test', ['default_langcode']);
    $expected = ['default_langcode'];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    $table_mapping->setExtraColumns('test', [
      'default_langcode',
      'default_revision',
    ]);
    $expected = ['default_langcode', 'default_revision'];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));

    // Test adding both field and extra columns.
    $table_mapping->setFieldNames('test', [
      'id',
      'name',
      'type',
      'description',
      'owner',
    ]);
    $table_mapping->setExtraColumns('test', [
      'default_langcode',
      'default_revision',
    ]);
    $expected = [
      'id',
      'name',
      'type',
      'description__value',
      'description__format',
      'owner__target_id',
      'owner__target_revision_id',
      'default_langcode',
      'default_revision',
    ];
    $this->assertSame($expected, $table_mapping->getAllColumns('test'));
  }

  /**
   * Tests DefaultTableMapping::getFieldNames().
   *
   * @covers ::getFieldNames
   * @covers ::setFieldNames
   */
  public function testGetFieldNames() {
    // The storage definitions are only used in getColumnNames() so we do not
    // need to provide any here.
    $table_mapping = new TestDefaultTableMapping($this->entityType, []);

    // Test that requesting the list of field names for a table for which no
    // fields have been added does not fail.
    $this->assertSame([], $table_mapping->getFieldNames('foo'));

    $return = $table_mapping->setFieldNames('foo', ['id', 'name', 'type']);
    $this->assertSame($table_mapping, $return);
    $expected = ['id', 'name', 'type'];
    $this->assertSame($expected, $table_mapping->getFieldNames('foo'));
    $this->assertSame([], $table_mapping->getFieldNames('bar'));

    $return = $table_mapping->setFieldNames('bar', ['description', 'owner']);
    $this->assertSame($table_mapping, $return);
    $expected = ['description', 'owner'];
    $this->assertSame($expected, $table_mapping->getFieldNames('bar'));
    // Test that the previously added field names are unaffected.
    $expected = ['id', 'name', 'type'];
    $this->assertSame($expected, $table_mapping->getFieldNames('foo'));
  }

  /**
   * Tests DefaultTableMapping::getColumnNames().
   *
   * @covers ::__construct
   * @covers ::getColumnNames
   */
  public function testGetColumnNames() {
    $definitions['test'] = $this->setUpDefinition('test', []);
    $table_mapping = new TestDefaultTableMapping($this->entityType, $definitions);
    $expected = [];
    $this->assertSame($expected, $table_mapping->getColumnNames('test'));

    $definitions['test'] = $this->setUpDefinition('test', ['value']);
    $table_mapping = new TestDefaultTableMapping($this->entityType, $definitions);
    $expected = ['value' => 'test'];
    $this->assertSame($expected, $table_mapping->getColumnNames('test'));

    $definitions['test'] = $this->setUpDefinition('test', ['value', 'format']);
    $table_mapping = new TestDefaultTableMapping($this->entityType, $definitions);
    $expected = ['value' => 'test__value', 'format' => 'test__format'];
    $this->assertSame($expected, $table_mapping->getColumnNames('test'));

    $definitions['test'] = $this->setUpDefinition('test', ['value']);
    // Set custom storage.
    $definitions['test']->expects($this->any())
      ->method('hasCustomStorage')
      ->wilLReturn(TRUE);
    $table_mapping = new TestDefaultTableMapping($this->entityType, $definitions);
    // Should return empty for column names.
    $this->assertSame([], $table_mapping->getColumnNames('test'));
  }

  /**
   * Tests DefaultTableMapping::getExtraColumns().
   *
   * @covers ::getExtraColumns
   * @covers ::setExtraColumns
   */
  public function testGetExtraColumns() {
    // The storage definitions are only used in getColumnNames() so we do not
    // need to provide any here.
    $table_mapping = new TestDefaultTableMapping($this->entityType, []);

    // Test that requesting the list of field names for a table for which no
    // fields have been added does not fail.
    $this->assertSame([], $table_mapping->getExtraColumns('foo'));

    $return = $table_mapping->setExtraColumns('foo', ['id', 'name', 'type']);
    $this->assertSame($table_mapping, $return);
    $expected = ['id', 'name', 'type'];
    $this->assertSame($expected, $table_mapping->getExtraColumns('foo'));
    $this->assertSame([], $table_mapping->getExtraColumns('bar'));

    $return = $table_mapping->setExtraColumns('bar', ['description', 'owner']);
    $this->assertSame($table_mapping, $return);
    $expected = ['description', 'owner'];
    $this->assertSame($expected, $table_mapping->getExtraColumns('bar'));
    // Test that the previously added field names are unaffected.
    $expected = ['id', 'name', 'type'];
    $this->assertSame($expected, $table_mapping->getExtraColumns('foo'));
  }

  /**
   * Tests DefaultTableMapping::getFieldColumnName() with valid parameters.
   *
   * @param bool $base_field
   *   Flag indicating whether the field should be treated as a base or bundle
   *   field.
   * @param string[] $columns
   *   An array of available field column names.
   * @param string $column
   *   The name of the column to be processed.
   * @param string $expected
   *   The expected result.
   *
   * @covers ::getFieldColumnName
   *
   * @dataProvider providerTestGetFieldColumnName
   */
  public function testGetFieldColumnName($base_field, $columns, $column, $expected) {
    $definitions['test'] = $this->setUpDefinition('test', $columns, $base_field);
    $table_mapping = new TestDefaultTableMapping($this->entityType, $definitions);
    $result = $table_mapping->getFieldColumnName($definitions['test'], $column);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests DefaultTableMapping::getFieldColumnName() with invalid parameters.
   *
   * @param bool $base_field
   *   Flag indicating whether the field should be treated as a base or bundle
   *   field.
   * @param string[] $columns
   *   An array of available field column names.
   * @param string $column
   *   The name of the column to be processed.
   *
   * @covers ::getFieldColumnName
   *
   * @dataProvider providerTestGetFieldColumnName
   */
  public function testGetFieldColumnNameInvalid($base_field, $columns, $column) {
    $definitions['test'] = $this->setUpDefinition('test', $columns, $base_field);

    // Mark field storage definition as custom storage.
    $definitions['test']->expects($this->any())
      ->method('hasCustomStorage')
      ->willReturn(TRUE);

    $table_mapping = new TestDefaultTableMapping($this->entityType, $definitions);
    $this->expectException(SqlContentEntityStorageException::class);
    $this->expectExceptionMessage("Column information not available for the 'test' field.");
    $table_mapping->getFieldColumnName($definitions['test'], $column);
  }

  /**
   * Provides test data for testGetFieldColumnName().
   *
   * @return array[]
   *   A nested array where each inner array has the following values: test
   *   field name, base field status, list of field columns, name of the column
   *   to be retrieved, expected result, whether an exception is expected.
   */
  public function providerTestGetFieldColumnName() {
    $data = [];
    // Base field with single column.
    $data[] = [TRUE, ['foo'], 'foo', 'test'];

    // Base field with multiple columns.
    $data[] = [TRUE, ['foo', 'bar'], 'foo', 'test__foo'];
    $data[] = [TRUE, ['foo', 'bar'], 'bar', 'test__bar'];
    // Bundle field with single column.
    $data[] = [FALSE, ['foo'], 'foo', 'test_foo'];
    // Bundle field with multiple columns.
    $data[] = [FALSE, ['foo', 'bar'], 'foo', 'test_foo'];
    $data[] = [FALSE, ['foo', 'bar'], 'bar', 'test_bar'];
    // Bundle field with reserved column.
    $data[] = [FALSE, ['foo', 'bar'], 'deleted', 'deleted'];

    return $data;
  }

  /**
   * Tests DefaultTableMapping::getFieldTableName().
   *
   * @param string[] $table_names
   *   An associative array of table names that should hold the field columns,
   *   where keys can be 'base', 'data' and 'revision'.
   * @param string $expected
   *   The expected table name.
   *
   * @covers ::getFieldTableName
   *
   * @dataProvider providerTestGetFieldTableName
   */
  public function testGetFieldTableName($table_names, $expected) {
    $field_name = 'test';
    $columns = ['test'];

    $definition = $this->setUpDefinition($field_name, $columns);
    $definition
      ->expects($this->any())
      ->method('getColumns')
      ->willReturn($columns);

    $this->entityType
      ->expects($this->any())
      ->method('getBaseTable')
      ->willReturn(isset($table_names['base']) ? $table_names['base'] : 'entity_test');

    $this->entityType
      ->expects($this->any())
      ->method('getDataTable')
      ->willReturn(isset($table_names['data']) ? $table_names['data'] : FALSE);

    $this->entityType
      ->expects($this->any())
      ->method('getRevisionTable')
      ->willReturn(isset($table_names['revision']) ? $table_names['revision'] : FALSE);

    $this->entityType
      ->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(isset($table_names['data']));

    $this->entityType
      ->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(isset($table_names['revision']));

    $this->entityType
      ->expects($this->any())
      ->method('getRevisionMetadataKeys')
      ->willReturn([]);

    $table_mapping = new TestDefaultTableMapping($this->entityType, [$field_name => $definition]);

    // Add the field to all the defined tables to ensure the correct one is
    // picked.
    foreach ($table_names as $table_name) {
      $table_mapping->setFieldNames($table_name, [$field_name]);
    }

    $this->assertEquals($expected, $table_mapping->getFieldTableName('test'));
  }

  /**
   * Provides test data for testGetFieldColumnName().
   *
   * @return array[]
   *   A nested array where each inner array has the following values: a list of
   *   table names and the expected table name.
   */
  public function providerTestGetFieldTableName() {
    $data = [];

    $data[] = [['data' => 'data_table', 'base' => 'base_table', 'revision' => 'revision_table'], 'data_table'];
    $data[] = [['data' => 'data_table', 'revision' => 'revision_table', 'base' => 'base_table'], 'data_table'];
    $data[] = [['base' => 'base_table', 'data' => 'data_table', 'revision' => 'revision_table'], 'data_table'];
    $data[] = [['base' => 'base_table', 'revision' => 'revision_table', 'data' => 'data_table'], 'data_table'];
    $data[] = [['revision' => 'revision_table', 'data' => 'data_table', 'base' => 'base_table'], 'data_table'];
    $data[] = [['revision' => 'revision_table', 'base' => 'base_table', 'data' => 'data_table'], 'data_table'];

    $data[] = [['data' => 'data_table', 'revision' => 'revision_table'], 'data_table'];
    $data[] = [['revision' => 'revision_table', 'data' => 'data_table'], 'data_table'];

    $data[] = [['base' => 'base_table', 'revision' => 'revision_table'], 'base_table'];
    $data[] = [['revision' => 'revision_table', 'base' => 'base_table'], 'base_table'];

    $data[] = [['data' => 'data_table'], 'data_table'];
    $data[] = [['base' => 'base_table'], 'base_table'];
    $data[] = [['revision' => 'revision_table'], 'revision_table'];

    return $data;
  }

  /**
   * Tests DefaultTableMapping::getFieldTableName() with an invalid parameter.
   *
   * @covers ::getFieldTableName
   */
  public function testGetFieldTableNameInvalid() {
    $table_mapping = new TestDefaultTableMapping($this->entityType, []);
    $this->expectException(SqlContentEntityStorageException::class);
    $this->expectExceptionMessage("Table information not available for the 'invalid_field_name' field.");
    $table_mapping->getFieldTableName('invalid_field_name');
  }

  /**
   * @covers ::getDedicatedDataTableName
   * @covers ::getDedicatedRevisionTableName
   *
   * @dataProvider providerTestGetDedicatedTableName
   */
  public function testGetDedicatedTableName($info, $expected_data_table, $expected_revision_table) {
    $entity_type_id = $info['entity_type_id'];
    $field_name = $info['field_name'];

    $definition = $this->setUpDefinition($field_name, []);
    $definition->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->will($this->returnValue($entity_type_id));
    $definition->expects($this->any())
      ->method('getUniqueStorageIdentifier')
      ->will($this->returnValue($entity_type_id . '-' . $field_name));

    $this->entityType
      ->expects($this->any())
      ->method('getBaseTable')
      ->willReturn($info['entity_type_id']);
    $this->entityType
      ->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(FALSE);
    $this->entityType
      ->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(FALSE);

    $table_mapping = new TestDefaultTableMapping($this->entityType, [], $info['prefix']);

    $this->assertSame($expected_data_table, $table_mapping->getDedicatedDataTableName($definition));
    $this->assertSame($expected_revision_table, $table_mapping->getDedicatedRevisionTableName($definition));
  }

  /**
   * Provides test data for testGetDedicatedTableName().
   *
   * @return array[]
   *   A nested array where each inner array has the following values: an array
   *   consisting of the entity type ID, field name and a table prefix, followed
   *   by the expected data table name and the revision table name.
   */
  public function providerTestGetDedicatedTableName() {
    $data = [];

    $data['short entity type; short field name; no prefix'] = [
      [
        'entity_type_id' => 'short_entity_type',
        'field_name' => 'short_field_name',
        'prefix' => '',
      ],
      'short_entity_type__short_field_name',
      'short_entity_type_revision__short_field_name',
    ];
    $data['short entity type; long field name; no prefix'] = [
      [
        'entity_type_id' => 'short_entity_type',
        'field_name' => 'long_field_name_abcdefghijklmnopqrstuvwxyz',
        'prefix' => '',
      ],
      'short_entity_type__28a01c7777',
      'short_entity_type_r__28a01c7777',
    ];
    $data['long entity type; short field name; no prefix'] = [
      [
        'entity_type_id' => 'long_entity_type_abcdefghijklmnopqrstuvwxyz',
        'field_name' => 'short_field_name',
        'prefix' => '',
      ],
      'long_entity_type_abcdefghijklmno__a526e4e042',
      'long_entity_type_abcdefghijklmno_r__a526e4e042',
    ];
    $data['long entity type; long field name; no prefix'] = [
      [
        'entity_type_id' => 'long_entity_type_abcdefghijklmnopqrstuvwxyz',
        'field_name' => 'long_field_name_abcdefghijklmnopqrstuvwxyz',
        'prefix' => '',
      ],
      'long_entity_type_abcdefghijklmno__7705d52d75',
      'long_entity_type_abcdefghijklmno_r__7705d52d75',
    ];

    $data['short entity type; short field name; with prefix'] = [
      [
        'entity_type_id' => 'short_entity_type',
        'field_name' => 'short_field_name',
        'prefix' => 'prefix_',
      ],
      'prefix_short_entity_type__short_field_name',
      'prefix_short_entity_type_r__a133cc765a',
    ];
    $data['short entity type; long field name; with prefix'] = [
      [
        'entity_type_id' => 'short_entity_type',
        'field_name' => 'long_field_name_abcdefghijklmnopqrstuvwxyz',
        'prefix' => 'prefix_',
      ],
      'prefix_short_entity_type__28a01c7777',
      'prefix_short_entity_type_r__28a01c7777',
    ];
    $data['long entity type; short field name; with prefix'] = [
      [
        'entity_type_id' => 'long_entity_type_abcdefghijklmnopqrstuvwxyz',
        'field_name' => 'short_field_name',
        'prefix' => 'prefix_',
      ],
      'prefix___a526e4e042',
      'prefix__r__a526e4e042',
    ];
    $data['long entity type; long field name; with prefix'] = [
      [
        'entity_type_id' => 'long_entity_type_abcdefghijklmnopqrstuvwxyz',
        'field_name' => 'long_field_name_abcdefghijklmnopqrstuvwxyz',
        'prefix' => 'prefix_',
      ],
      'prefix___7705d52d75',
      'prefix__r__7705d52d75',
    ];

    return $data;
  }

  /**
   * Sets up a field storage definition for the test.
   *
   * @param string $name
   *   The field name.
   * @param array $column_names
   *   An array of column names for the storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function setUpDefinition($name, array $column_names, $base_field = TRUE) {
    $definition = $this->createMock('Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface');
    $definition->expects($this->any())
      ->method('isBaseField')
      ->willReturn($base_field);
    $definition->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($name));
    $definition->expects($this->any())
      ->method('getColumns')
      ->will($this->returnValue(array_fill_keys($column_names, [])));
    return $definition;
  }

}

/**
 * Extends DefaultTableMapping to allow calling its protected methods.
 */
class TestDefaultTableMapping extends DefaultTableMapping {

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
