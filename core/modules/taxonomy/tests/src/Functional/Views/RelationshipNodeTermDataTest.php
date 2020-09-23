<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\views\Views;
use Drupal\views\ViewExecutable;

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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

  /**
   * Tests that the 'taxonomy_term_access' tag is added to the Views query.
   */
  public function testTag() {
    // Change the view to test relation limited by vocabulary.
    $this->config('views.view.test_taxonomy_node_term_data')
      ->set('display.default.display_options.relationships.term_node_tid.vids', ['views_testing_tags'])
      ->save();
    $view = Views::getView('test_taxonomy_node_term_data');
    $this->executeView($view, [$this->term1->id()]);

    // By default, view has taxonomy_term_access tag.
    $this->assertQueriesTermAccessTag($view, TRUE);

    // The term_access tag is not set if disable_sql_rewrite is set.
    $view = Views::getView('test_taxonomy_node_term_data');
    $display = $view->getDisplay();
    $display_options = $display->getOption('query');
    $display_options['options']['disable_sql_rewrite'] = TRUE;
    $display->setOption('query', $display_options);
    $view->save();
    $this->executeView($view, [$this->term1->id()]);

    $this->assertQueriesTermAccessTag($view, FALSE);
  }

  /**
   * Assert views queries have taxonomy_term_access tag.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The View to check for the term access tag.
   * @param bool $hasTag
   *   The expected existence of taxonomy_term_access tag.
   */
  protected function assertQueriesTermAccessTag(ViewExecutable $view, $hasTag) {
    $main_query = $view->build_info['query'];
    $count_query = $view->build_info['count_query'];

    foreach ([$main_query, $count_query] as $query) {
      $tables = $query->getTables();
      foreach ($tables as $join_table) {
        if (is_object($join_table['table'])) {
          $this->assertSame($join_table['table']->hasTag('taxonomy_term_access'), $hasTag);
        }
      }
    }
  }

}
