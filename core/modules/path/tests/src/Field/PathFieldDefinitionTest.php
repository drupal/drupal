<?php

/**
 * @file
 * Contains \Drupal\path\Tests\Plugin\Field\FieldType\PathFieldDefinitionTest
 */

namespace Drupal\path\Tests\Field;

use Drupal\Tests\Core\Field\FieldDefinitionTestBase;

/**
 * Tests a field definition for a 'path' field.
 *
 * @see \Drupal\Core\Field\FieldDefinition
 * @see \Drupal\path\Plugin\Field\FieldType\PathItem
 *
 * @group Drupal
 * @group path
 */
class PathFieldDefinitionTest extends FieldDefinitionTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Path field definitions',
      'description' => 'Tests that field definitions for path fields work correctly.',
      'group' => 'Path',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginId() {
    return 'path';
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleAndPath() {
    return array('path', dirname(dirname(dirname(__DIR__))));
  }

  /**
   * Tests FieldDefinition::getColumns().
   *
   * @covers \Drupal\Core\Field\FieldDefinition::getColumns
   * @covers \Drupal\path\Plugin\Field\FieldType\PathItem::getSchema
   */
  public function testGetColumns() {
    $this->assertSame(array(), $this->definition->getColumns());
  }

}
