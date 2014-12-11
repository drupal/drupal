<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\Views\SelectionTest.
 */

namespace Drupal\entity_reference\Tests\Views;

use Drupal\simpletest\WebTestBase;

/**
 * Tests entity reference selection handler.
 *
 * @group entity_reference
 */
class SelectionTest extends WebTestBase {

  public static $modules = array('node', 'views', 'entity_reference', 'entity_reference_test', 'entity_test');

  /**
   * Tests the selection handler.
   */
  public function testSelectionHandler() {
    // Create nodes.
    $type = $this->drupalCreateContentType()->type;
    $node1 = $this->drupalCreateNode(array('type' => $type));
    $node2 = $this->drupalCreateNode(array('type' => $type));
    $node3 = $this->drupalCreateNode();

    $nodes = array();
    foreach (array($node1, $node2, $node3) as $node) {
      $nodes[$node->getType()][$node->id()] = $node->label();
    }

    // Create a field.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'translatable' => FALSE,
      'settings' => array(
        'target_type' => 'node',
      ),
      'type' => 'entity_reference',
      'cardinality' => '1',
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'test_bundle',
      'settings' => array(
        'handler' => 'views',
        'handler_settings' => array(
          'view' => array(
            'view_name' => 'test_entity_reference',
            'display_name' => 'entity_reference_1',
            'arguments' => array(),
          ),
        ),
      ),
    ));
    $field->save();

    // Get values from selection handler.
    $handler = $this->container->get('plugin.manager.entity_reference.selection')->getSelectionHandler($field);
    $result = $handler->getReferenceableEntities();

    $success = FALSE;
    foreach ($result as $node_type => $values) {
      foreach ($values as $nid => $label) {
        if (!$success = $nodes[$node_type][$nid] == trim(strip_tags($label))) {
          // There was some error, so break.
          break;
        }
      }
    }

    $this->assertTrue($success, 'Views selection handler returned expected values.');
  }
}
