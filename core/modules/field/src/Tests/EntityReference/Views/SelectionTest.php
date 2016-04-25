<?php

namespace Drupal\field\Tests\EntityReference\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\views\Views;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests entity reference selection handler.
 *
 * @group entity_reference
 */
class SelectionTest extends WebTestBase {

  public static $modules = ['node', 'views', 'entity_reference_test', 'entity_test'];

  /**
   * Nodes for testing.
   *
   * @var array
   */
  protected $nodes = array();

  /**
   * The entity reference field to test.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create nodes.
    $type = $this->drupalCreateContentType()->id();
    $node1 = $this->drupalCreateNode(array('type' => $type));
    $node2 = $this->drupalCreateNode(array('type' => $type));
    $node3 = $this->drupalCreateNode();

    foreach (array($node1, $node2, $node3) as $node) {
      $this->nodes[$node->getType()][$node->id()] = $node->label();
    }

    // Create a field.
    $field_storage = FieldStorageConfig::create(array(
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
    $field = FieldConfig::create([
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
    ]);
    $field->save();
    $this->field = $field;
  }

  /**
   * Confirm the expected results are returned.
   *
   * @param array $result
   *   Query results keyed by node type and nid.
   */
  protected function assertResults(array $result) {
    $success = FALSE;
    foreach ($result as $node_type => $values) {
      foreach ($values as $nid => $label) {
        if (!$success = $this->nodes[$node_type][$nid] == trim(strip_tags($label))) {
          // There was some error, so break.
          break;
        }
      }
    }
    $this->assertTrue($success, 'Views selection handler returned expected values.');
  }

  /**
   * Tests the selection handler.
   */
  public function testSelectionHandler() {
    // Get values from selection handler.
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->field);
    $result = $handler->getReferenceableEntities();
    $this->assertResults($result);
  }

  /**
   * Tests the selection handler with a relationship.
   */
  public function testSelectionHandlerRelationship() {
    // Add a relationship to the view.
    $view = Views::getView('test_entity_reference');
    $view->setDisplay();
    $view->displayHandlers->get('default')->setOption('relationships', array(
      'test_relationship' => array(
        'id' => 'uid',
        'table' => 'node_field_data',
        'field' => 'uid',
      ),
    ));

    // Add a filter depending on the relationship to the test view.
    $view->displayHandlers->get('default')->setOption('filters', array(
      'uid' => array(
        'id' => 'uid',
        'table' => 'users_field_data',
        'field' => 'uid',
        'relationship' => 'test_relationship',
      )
    ));

    // Set view to distinct so only one row per node is returned.
    $query_options = $view->display_handler->getOption('query');
    $query_options['options']['distinct'] = TRUE;
    $view->display_handler->setOption('query', $query_options);
    $view->save();

    // Get values from the selection handler.
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->field);
    $result = $handler->getReferenceableEntities();
    $this->assertResults($result);
  }
}
