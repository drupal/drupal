<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Sql\DefaultTableMappingTest.
 */

namespace Drupal\Tests\Core\Entity\Sql;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Sql\DefaultTableMapping
 * @group Entity
 */
class DefaultTableMappingTest extends UnitTestCase {

  /**
   * Tests DefaultTableMapping::getTableNames().
   *
   * @covers ::getTableNames
   */
  public function testGetTableNames() {
    // The storage definitions are only used in getColumnNames() so we do not
    // need to provide any here.
    $table_mapping = new DefaultTableMapping([]);
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

    $table_mapping = new DefaultTableMapping($definitions);
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
    $table_mapping = new DefaultTableMapping([]);

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
    $table_mapping = new DefaultTableMapping($definitions);
    $expected = [];
    $this->assertSame($expected, $table_mapping->getColumnNames('test'));

    $definitions['test'] = $this->setUpDefinition('test', ['value']);
    $table_mapping = new DefaultTableMapping($definitions);
    $expected = ['value' => 'test'];
    $this->assertSame($expected, $table_mapping->getColumnNames('test'));

    $definitions['test'] = $this->setUpDefinition('test', ['value', 'format']);
    $table_mapping = new DefaultTableMapping($definitions);
    $expected = ['value' => 'test__value', 'format' => 'test__format'];
    $this->assertSame($expected, $table_mapping->getColumnNames('test'));
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
    $table_mapping = new DefaultTableMapping([]);

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
   * Sets up a field storage definition for the test.
   *
   * @param string $name
   *   The field name.
   * @param array $column_names
   *   An array of column names for the storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function setUpDefinition($name, array $column_names) {
    $definition = $this->getMock('Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface');
    $definition->expects($this->any())
      ->method('isBaseField')
      ->will($this->returnValue(TRUE));
    $definition->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($name));
    $definition->expects($this->any())
      ->method('getColumns')
      ->will($this->returnValue(array_fill_keys($column_names, [])));
    return $definition;
  }

}
