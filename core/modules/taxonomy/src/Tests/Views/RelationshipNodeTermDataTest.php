<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\RelationshipNodeTermDataTest.
 */

namespace Drupal\taxonomy\Tests\Views;

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
  public static $testViews = array('test_taxonomy_node_term_data');

  function testViewsHandlerRelationshipNodeTermData() {
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
    $this->assertIdentical($expected, $view->calculateDependencies());
    $this->executeView($view, array($this->term1->id(), $this->term2->id()));
    $expected_result = array(
      array(
        'nid' => $this->nodes[1]->id(),
      ),
      array(
        'nid' => $this->nodes[0]->id(),
      ),
    );
    $column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($view, $expected_result, $column_map);

    // Change the view to test relation limited by vocabulary.
    \Drupal::config('views.view.test_taxonomy_node_term_data')
      ->set('display.default.display_options.relationships.term_node_tid.vids', ['views_testing_tags'])
      ->save();

    $view = Views::getView('test_taxonomy_node_term_data');
    // Tests \Drupal\taxonomy\Plugin\views\relationship\NodeTermData::calculateDependencies().
    $expected['config'][] = 'taxonomy.vocabulary.views_testing_tags';
    $this->assertIdentical($expected, $view->calculateDependencies());
    $this->executeView($view, array($this->term1->id(), $this->term2->id()));
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
