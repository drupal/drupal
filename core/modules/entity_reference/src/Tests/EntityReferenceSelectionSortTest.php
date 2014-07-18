<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceSelectionSortTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

/**
 * Tests sorting referenced items.
 *
 * @group entity_reference
 */
class EntityReferenceSelectionSortTest extends WebTestBase {

  public static $modules = array('node', 'entity_reference', 'entity_test');

  function setUp() {
    parent::setUp();

    // Create an Article node type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Assert sorting by field and property.
   */
  public function testSort() {
    // Add text field to entity, to sort by.
    entity_create('field_storage_config', array(
      'name' => 'field_text',
      'entity_type' => 'node',
      'type' => 'text',
      'entity_types' => array('node'),
    ))->save();

    entity_create('field_instance_config', array(
      'label' => 'Text Field',
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => array(),
      'required' => FALSE,
    ))->save();


    // Create a field storage and instance.
    $field_storage = entity_create('field_storage_config', array(
      'name' => 'test_field',
      'entity_type' => 'entity_test',
      'translatable' => FALSE,
      'settings' => array(
        'target_type' => 'node',
      ),
      'type' => 'entity_reference',
      'cardinality' => 1,
    ));
    $field_storage->save();
    $instance = entity_create('field_instance_config', array(
      'field_storage' => $field_storage,
      'entity_type' => 'entity_test',
      'bundle' => 'test_bundle',
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array(),
          // Add sorting.
          'sort' => array(
            'field' => 'field_text.value',
            'direction' => 'DESC',
          ),
        ),
      ),
    ));
    $instance->save();

    // Build a set of test data.
    $node_values = array(
      'published1' => array(
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published1 (<&>)',
        'uid' => 1,
        'field_text' => array(
          array(
            'value' => 1,
          ),
        ),
      ),
      'published2' => array(
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published2 (<&>)',
        'uid' => 1,
        'field_text' => array(
          array(
            'value' => 2,
          ),
        ),
      ),
    );

    $nodes = array();
    $node_labels = array();
    foreach ($node_values as $key => $values) {
      $node = entity_create('node', $values);
      $node->save();
      $nodes[$key] = $node;
      $node_labels[$key] = String::checkPlain($node->label());
    }

    // Test as a non-admin.
    $normal_user = $this->drupalCreateUser(array('access content'));
    \Drupal::currentUser()->setAccount($normal_user);

    $handler = $this->container->get('plugin.manager.entity_reference.selection')->getSelectionHandler($instance);

    // Not only assert the result, but make sure the keys are sorted as
    // expected.
    $result = $handler->getReferenceableEntities();
    $expected_result = array(
      $nodes['published2']->id() => $node_labels['published2'],
      $nodes['published1']->id() => $node_labels['published1'],
    );
    $this->assertIdentical($result['article'], $expected_result, 'Query sorted by field returned expected values.');

    // Assert sort by property.
    $instance->settings['handler_settings']['sort'] = array(
      'field' => 'nid',
      'direction' => 'ASC',
    );
    $handler = $this->container->get('plugin.manager.entity_reference.selection')->getSelectionHandler($instance);
    $result = $handler->getReferenceableEntities();
    $expected_result = array(
      $nodes['published1']->id() => $node_labels['published1'],
      $nodes['published2']->id() => $node_labels['published2'],
    );
    $this->assertIdentical($result['article'], $expected_result, 'Query sorted by property returned expected values.');
  }
}
