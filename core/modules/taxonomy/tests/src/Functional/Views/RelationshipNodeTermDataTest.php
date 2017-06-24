<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\views\Views;

/**
 * Tests the taxonomy term on node relationship handler.
 *
 * @group taxonomy
 */
class RelationshipNodeTermDataTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_taxonomy_node_term_data'];

  public function testViewsHandlerRelationshipNodeTermData() {
    $view = Views::getView('test_taxonomy_node_term_data');
    // Tests \Drupal\taxonomy\Plugin\views\relationship\NodeTermData::calculateDependencies().
    $expected = [
      'config' => ['core.entity_view_mode.node.teaser'],
      'module' => [
        'node',
        'taxonomy',
        'user',
      ],
    ];
    $this->assertIdentical($expected, $view->getDependencies());
    $this->executeView($view, [$this->term1->id(), $this->term2->id()]);
    $expected_result = [
      [
        'nid' => $this->nodes[1]->id(),
      ],
      [
        'nid' => $this->nodes[0]->id(),
      ],
    ];
    $column_map = ['nid' => 'nid'];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);

    // Change the view to test relation limited by vocabulary.
    $this->config('views.view.test_taxonomy_node_term_data')
      ->set('display.default.display_options.relationships.term_node_tid.vids', ['views_testing_tags'])
      ->save();

    $view = Views::getView('test_taxonomy_node_term_data');
    // Tests \Drupal\taxonomy\Plugin\views\relationship\NodeTermData::calculateDependencies().
    $expected['config'][] = 'taxonomy.vocabulary.views_testing_tags';
    $this->assertIdentical($expected, $view->getDependencies());
    $this->executeView($view, [$this->term1->id(), $this->term2->id()]);
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
