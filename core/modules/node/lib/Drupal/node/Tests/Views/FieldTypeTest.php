<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\FieldTypeTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the Drupal\node\Plugin\views\field\Type handler.
 */
class FieldTypeTest extends NodeTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_field_type');

  public static function getInfo() {
    return array(
      'name' => 'Node: Node Type field',
      'description' => 'Tests the Drupal\node\Plugin\views\field\Type handler.',
      'group' => 'Views module integration',
    );
  }

  public function testFieldType() {
    $node = $this->drupalCreateNode();
    $expected_result[] = array(
      'nid' => $node->id(),
      'node_field_data_type' => $node->bundle(),
    );
    $column_map = array(
      'nid' => 'nid',
      'node_field_data_type' => 'node_field_data_type',
    );

    $view = Views::getView('test_field_type');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, $expected_result, $column_map, 'The correct node type was displayed.');
  }

}
