<?php

namespace Drupal\KernelTests\Core\Render;

use Drupal\Core\Render\Element\Table;
use Drupal\KernelTests\KernelTestBase;

/**
 * Deprecation tests cases for the render layer.
 *
 * @group legacy
 */
class RendererLegacyTest extends KernelTestBase {

  /**
   * Tests deprecation of the drupal_attach_tabledrag() function.
   *
   * @expectedDeprecation drupal_attach_tabledrag() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Render\Element\Table::attachTabledrag() instead. See https://www.drupal.org/node/3035565
   */
  public function testTableDrag() {
    $elements = [];
    $options = [
      'table_id' => 'test-table',
      'action' => 'match',
      'relationship' => 'sibling',
      'group' => 'test',
    ];
    drupal_attach_tabledrag($elements, $options);
    $expected = [];
    Table::attachTabledrag($expected, $options);
    $this->assertSame($expected['#attached']['drupalSettings']['tableDrag']['test-table']['test'][1], $elements['#attached']['drupalSettings']['tableDrag']['test-table']['test'][0]);
  }

}
