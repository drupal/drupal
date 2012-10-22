<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Node\FieldTypeTest.
 */

namespace Drupal\views\Tests\Node;

/**
 * Tests the Views\node\Plugin\views\field\Type handler.
 */
class FieldTypeTest extends NodeTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Node: Node Type field',
      'description' => 'Tests the Views\node\Plugin\views\field\Type handler.',
      'group' => 'Views Modules',
    );
  }

  public function testFieldType() {
    $node = $this->drupalCreateNode();
    $expected_result[] = array(
      'nid' => $node->id(),
      'node_type' => $node->bundle(),
    );
    $column_map = array(
      'nid' => 'nid',
      'node_type' => 'node_type',
    );

    $view = $this->getView();
    $view->preview();
    $this->executeView($view);
    $this->assertIdenticalResultset($view, $expected_result, $column_map, 'The correct node type was displayed.');
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_field_type');
  }

}
