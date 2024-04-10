<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\views\Views;

/**
 * Test the taxonomy term with depth argument.
 *
 * @group taxonomy
 */
class TaxonomyTermArgumentDepthTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'taxonomy_test_views',
    'views',
    'node',
  ];

  /**
   * Views IDs used by this test.
   *
   * @var string[]
   */
  public static $testViews = ['test_argument_taxonomy_index_tid_depth'];

  /**
   * The terms used in the test.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * The view executable used in the test.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Install node_access schema in order to successfully re-save nodes.
    $this->installSchema('node', ['node_access']);

    // Create a hierarchy 5 deep. Note the parent setup function creates two
    // top-level terms w/o children.
    $first = $this->createTerm(['name' => 'First']);
    $second = $this->createTerm(['name' => 'Second', 'parent' => $first->id()]);
    $third = $this->createTerm(['name' => 'Third', 'parent' => $second->id()]);
    $fourth = $this->createTerm(['name' => 'Fourth', 'parent' => $third->id()]);
    $fifth = $this->createTerm(['name' => 'Fifth', 'parent' => $fourth->id()]);
    $this->terms = [$first, $second, $third, $fourth, $fifth];

    // Create a node w/o any terms.
    $settings = ['type' => 'article'];
    $this->nodes[] = $this->drupalCreateNode($settings);

    // Create a node with only the top level term.
    $settings['field_views_testing_tags'][0]['target_id'] = $first->id();
    $this->nodes[] = $this->drupalCreateNode($settings);

    // Create a node with only the third level term.
    $settings['field_views_testing_tags'][0]['target_id'] = $third->id();
    $this->nodes[] = $this->drupalCreateNode($settings);

    // Create a node with only the fifth level term.
    $settings['field_views_testing_tags'][0]['target_id'] = $fifth->id();
    $this->nodes[] = $this->drupalCreateNode($settings);

    $this->view = Views::getView(self::$testViews[0]);

    // Fix the created date to match the expectations of the order by in the
    // view. Node 1 should be the most recent node and node 6 should be the
    // oldest.
    $time = \Drupal::time();
    foreach ($this->nodes as $i => $node) {
      $node->setCreatedTime($time->getRequestTime() - $i)->save();
    }
  }

  /**
   * Tests the terms with depth filter.
   */
  public function testTermWithDepthFilter(): void {
    // Default view has an empty value for this filter, so all nodes should be
    // returned.
    $expected = [
      ['nid' => 1],
      ['nid' => 2],
      ['nid' => 3],
      ['nid' => 4],
      ['nid' => 5],
      ['nid' => 6],
    ];
    $this->executeView($this->view);
    $this->assertIdenticalResultsetHelper($this->view, $expected, ['nid' => 'nid'], 'assertIdentical');

    // Set filter to search on top-level term, with depth 0.
    $expected = [['nid' => 4]];
    $this->assertTermWithDepthResult($expected, $this->terms[0]->id(), 0);

    // Top-level term, depth 1.
    $expected = [['nid' => 4]];
    $this->assertTermWithDepthResult($expected, $this->terms[0]->id(), 1);

    // Top-level term, depth 2.
    $expected = [['nid' => 4], ['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[0]->id(), 2);

    // Top-level term, depth 9.
    $expected = [['nid' => 4], ['nid' => 5], ['nid' => 6]];
    $this->assertTermWithDepthResult($expected, $this->terms[0]->id(), 9);

    // Second-level term, depth 1.
    $expected = [['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[1]->id(), 1);

    // Third-level term, depth 0.
    $expected = [['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[2]->id(), 0);

    // Third-level term, depth 1.
    $expected = [['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[2]->id(), 1);

    // Third-level term, depth -2.
    $expected = [['nid' => 4], ['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[2]->id(), -2);

    // Third-level term, depth -9.
    $expected = [['nid' => 4], ['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[2]->id(), -9);

    // Fifth-level term, depth -9.
    $expected = [['nid' => 4], ['nid' => 5], ['nid' => 6]];
    $this->assertTermWithDepthResult($expected, $this->terms[4]->id(), -9);

    // Third-level term, depth -1.
    $expected = [['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[2]->id(), -1);

    // Third-level and second-level term, depth -1, using a plus sign.
    $expected = [['nid' => 4], ['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[2]->id() . '+' . $this->terms[1]->id(), -1, TRUE);

    // Third-level and second-level term, depth -1, using a comma. Note that due
    // to performance the "and" meaning of comma is not supported.
    $expected = [['nid' => 4], ['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[2]->id() . ',' . $this->terms[1]->id(), -1, TRUE);

    // Top-level term and second level term, depth 1, using a plus sign.
    $expected = [['nid' => 4], ['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[0]->id() . '+' . $this->terms[1]->id(), 1, TRUE);

    // Top-level term and second level term, depth 1, using a comma. Note that
    // due to performance the "and" meaning of comma is not supported.
    $expected = [['nid' => 4], ['nid' => 5]];
    $this->assertTermWithDepthResult($expected, $this->terms[0]->id() . ',' . $this->terms[1]->id(), 1, TRUE);
  }

  /**
   * Asserts the result of the view for the given arguments.
   *
   * @param array $expected
   *   The expected views result.
   * @param int|string $tid
   *   The term ID or IDs to use as an argument.
   * @param int $depth
   *   The depth to search.
   * @param bool $break_phrase
   *   Whether to break the argument up into multiple terms.
   *
   * @internal
   */
  protected function assertTermWithDepthResult(array $expected, $tid, int $depth, bool $break_phrase = FALSE): void {
    $this->view->destroy();
    $this->view->initDisplay();
    $arguments = $this->view->displayHandlers->get('default')->getOption('arguments');
    $arguments['term_node_tid_depth']['depth'] = $depth;
    $arguments['term_node_tid_depth']['break_phrase'] = $break_phrase;
    $this->view->displayHandlers->get('default')->setOption('arguments', $arguments);
    $this->executeView($this->view, [$tid]);
    $this->assertIdenticalResultsetHelper($this->view, $expected, ['nid' => 'nid'], 'assertIdentical');
  }

}
