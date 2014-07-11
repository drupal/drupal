<?php

/**
 * @file
 * Contains \Drupal\path\Tests\Plugin\Field\FieldType\PathFieldDefinitionTest
 */

namespace Drupal\path\Tests\Field;

use Drupal\Tests\Core\Field\FieldDefinitionTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Field\FieldDefinition
 * @group path
 */
class PathFieldDefinitionTest extends FieldDefinitionTestBase {

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
   * @covers \Drupal\path\Plugin\Field\FieldType\PathItem::schema
   */
  public function testGetColumns() {
    $this->assertSame(array(), $this->definition->getColumns());
  }

}
