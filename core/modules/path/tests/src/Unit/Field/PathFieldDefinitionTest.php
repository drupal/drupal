<?php

/**
 * @file
 * Contains \Drupal\Tests\path\Unit\Plugin\Field\FieldType\PathFieldDefinitionTest.
 */

namespace Drupal\Tests\path\Unit\Field;

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
    return array('path', dirname(dirname(dirname(dirname(__DIR__)))));
  }

  /**
   * Tests BaseFieldDefinition::getColumns().
   *
   * @covers ::getColumns
   * @covers ::schema
   */
  public function testGetColumns() {
    $this->assertSame(array(), $this->definition->getColumns());
  }

}
