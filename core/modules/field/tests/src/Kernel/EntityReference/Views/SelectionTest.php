<?php

namespace Drupal\Tests\field\Kernel\EntityReference\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Views;

/**
 * Tests entity reference selection handler.
 *
 * @group entity_reference
 */
class SelectionTest extends KernelTestBase {

  use EntityReferenceTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_reference_test',
    'entity_test',
    'field',
    'filter',
    'node',
    'system',
    'user',
    'views',
  ];

  /**
   * Nodes for testing.
   *
   * @var string[][]
   */
  protected $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['entity_reference_test', 'filter']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create test nodes.
    $type = strtolower($this->randomMachineName());
    NodeType::create(['type' => $type])->save();
    $node1 = $this->createNode(['type' => $type]);
    $node2 = $this->createNode(['type' => $type]);
    $node3 = $this->createNode();

    foreach ([$node1, $node2, $node3] as $node) {
      $this->nodes[$node->bundle()][$node->id()] = $node->label();
    }

    // Create an entity reference field.
    $handler_settings = [
      'view' => [
        'view_name' => 'test_entity_reference',
        'display_name' => 'entity_reference_1',
      ],
    ];
    $this->createEntityReferenceField('entity_test', 'test_bundle', 'test_field', $this->randomString(), 'node', 'views', $handler_settings);
  }

  /**
   * Tests the selection handler.
   */
  public function testSelectionHandler() {
    $field_config = FieldConfig::loadByName('entity_test', 'test_bundle', 'test_field');
    $selection_handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config);

    // Tests the selection handler.
    $result = $selection_handler->getReferenceableEntities();
    $this->assertResults($result);

    // Add a relationship to the view.
    $view = Views::getView('test_entity_reference');
    $view->setDisplay();
    $view->displayHandlers->get('default')->setOption('relationships', [
      'test_relationship' => [
        'id' => 'uid',
        'table' => 'node_field_data',
        'field' => 'uid',
      ],
    ]);

    // Add a filter depending on the relationship to the test view.
    $view->displayHandlers->get('default')->setOption('filters', [
      'uid' => [
        'id' => 'uid',
        'table' => 'users_field_data',
        'field' => 'uid',
        'relationship' => 'test_relationship',
      ],
    ]);

    // Set view to distinct so only one row per node is returned.
    $query_options = $view->display_handler->getOption('query');
    $query_options['options']['distinct'] = TRUE;
    $view->display_handler->setOption('query', $query_options);
    $view->save();

    // Tests the selection handler with a relationship.
    $result = $selection_handler->getReferenceableEntities();
    $this->assertResults($result);
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

}
