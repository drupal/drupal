<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

/**
 * Tests the taxonomy term with depth argument.
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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_argument_taxonomy_index_tid_depth'];

  /**
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Create a term with markup in the label.
    $first = $this->createTerm(['name' => '<em>First</em>']);

    // Create a node w/o any terms.
    $settings = ['type' => 'article'];

    // Create a node with linked to the term.
    $settings['field_views_testing_tags'][0]['target_id'] = $first->id();
    $this->nodes[] = $this->drupalCreateNode($settings);

    $this->terms[0] = $first;
  }

  /**
   * Tests title escaping.
   */
  public function testTermWithDepthArgumentTitleEscaping() {
    $this->drupalGet('test_argument_taxonomy_index_tid_depth/' . $this->terms[0]->id());
    $this->assertSession()->assertEscaped($this->terms[0]->label());
  }

}
