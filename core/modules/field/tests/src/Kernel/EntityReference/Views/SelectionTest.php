<?php

namespace Drupal\Tests\field\Kernel\EntityReference\Views;

use Drupal\Component\Utility\Html;
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
   * The selection handler.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
      $this->nodes[$node->id()] = $node;
    }

    // Create an entity reference field.
    $handler_settings = [
      'view' => [
        'view_name' => 'test_entity_reference',
        'display_name' => 'entity_reference_1',
      ],
    ];
    $this->createEntityReferenceField('entity_test', 'test_bundle', 'test_field', $this->randomString(), 'node', 'views', $handler_settings);
    $field_config = FieldConfig::loadByName('entity_test', 'test_bundle', 'test_field');
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config);
  }

  /**
   * Tests the selection handler.
   */
  public function testSelectionHandler() {
    // Tests the selection handler.
    $this->assertResults($this->selectionHandler->getReferenceableEntities());

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
    $this->assertResults($this->selectionHandler->getReferenceableEntities());
  }

  /**
   * Tests the anchor tag stripping.
   *
   * Unstripped results based on the data above will result in output like so:
   *   ...<a href="/node/1" hreflang="en">Test first node</a>...
   *   ...<a href="/node/2" hreflang="en">Test second node</a>...
   *   ...<a href="/node/3" hreflang="en">Test third node</a>...
   * If we expect our output to not have the <a> tags, and this matches what's
   * produced by the tag-stripping method, we'll know that it's working.
   */
  public function testAnchorTagStripping() {
    $filtered_rendered_results_formatted = [];
    foreach ($this->selectionHandler->getReferenceableEntities() as $subresults) {
      $filtered_rendered_results_formatted += $subresults;
    }

    // Note the missing <a> tags.
    $expected = [
      1 => '<span class="views-field views-field-title"><span class="field-content">' . Html::escape($this->nodes[1]->label()) . '</span></span>',
      2 => '<span class="views-field views-field-title"><span class="field-content">' . Html::escape($this->nodes[2]->label()) . '</span></span>',
      3 => '<span class="views-field views-field-title"><span class="field-content">' . Html::escape($this->nodes[3]->label()) . '</span></span>',
    ];

    $this->assertEqual($filtered_rendered_results_formatted, $expected, 'Anchor tag stripping has failed.');
  }

  /**
   * Confirm the expected results are returned.
   *
   * @param array $result
   *   Query results keyed by node type and nid.
   */
  protected function assertResults(array $result) {
    foreach ($result as $node_type => $values) {
      foreach ($values as $nid => $label) {
        $this->assertSame($node_type, $this->nodes[$nid]->bundle());
        $this->assertSame(trim(strip_tags($label)), Html::escape($this->nodes[$nid]->label()));
      }
    }
  }

}
