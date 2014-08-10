<?php

/**
 * @file
 * Contains \Drupal\path\Tests\Plugin\Field\FieldType\PathFieldDefinitionTest.
 */

namespace Drupal\path\Tests\Field;

use Drupal\Tests\Core\Field\BaseFieldDefinitionTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Field\BaseFieldDefinition
 * @group path
 */
class PathFieldDefinitionTest extends BaseFieldDefinitionTestBase {

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
   * Tests BaseFieldDefinition::getColumns().
   *
   * @covers \Drupal\Core\Field\BaseFieldDefinition::getColumns
   * @covers \Drupal\path\Plugin\Field\FieldType\PathItem::schema
   */
  public function testGetColumns() {
    $this->assertSame(array(), $this->definition->getColumns());
  }

}
