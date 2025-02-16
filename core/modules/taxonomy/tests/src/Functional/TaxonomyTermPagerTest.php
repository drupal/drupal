<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

/**
 * Ensures that the term pager works properly.
 *
 * @group taxonomy
 */
class TaxonomyTermPagerTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'taxonomy_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'bypass node access',
    ]));
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Tests that the pager is displayed properly on the term overview page.
   */
  public function testTaxonomyTermOverviewPager(): void {
    // Set limit to 3 terms per page.
    $this->config('taxonomy.settings')
      ->set('terms_per_page_admin', '3')
      ->save();

    // Create 3 terms.
    for ($x = 1; $x <= 3; $x++) {
      $this->createTerm($this->vocabulary);
    }

    // Get Page 1.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    // Pager should not be visible.
    $this->assertSession()->responseNotMatches('|<nav class="pager" [^>]*>|');

    // Create 3 more terms to show pager.
    for ($x = 1; $x <= 3; $x++) {
      $this->createTerm($this->vocabulary);
    }

    // Ensure that pager is visible on page 1.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertSession()->responseMatches('|<nav class="pager" [^>]*>|');

    // Ensure that pager is visible on page 2.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview', ['query' => ['page' => 1]]);
    $this->assertSession()->responseMatches('|<nav class="pager" [^>]*>|');
  }

  /**
   * Tests that overview page only loads the necessary terms.
   */
  public function testTaxonomyTermOverviewTermLoad(): void {
    // Set limit to 3 terms per page.
    $this->config('taxonomy.settings')
      ->set('terms_per_page_admin', '3')
      ->save();

    $state = $this->container->get('state');

    // Create 5 terms.
    for ($x = 0; $x <= 10; $x++) {
      $this->createTerm($this->vocabulary, ['weight' => $x]);
    }

    // Check the overview page.
    $state->set('taxonomy_test_taxonomy_term_load', []);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $loaded_terms = $state->get('taxonomy_test_taxonomy_term_load');
    $this->assertCount(4, $loaded_terms);

    // Check the overview page for submit callback.
    $state->set('taxonomy_test_taxonomy_term_load', []);
    $this->submitForm([], 'Save');
    $loaded_terms = $state->get('taxonomy_test_taxonomy_term_load');
    $this->assertCount(4, $loaded_terms);

    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview', ['query' => ['page' => 2]]);
    $state->set('taxonomy_test_taxonomy_term_load', []);
    $this->submitForm([], 'Save');
    $loaded_terms = $state->get('taxonomy_test_taxonomy_term_load');
    $this->assertCount(4, $loaded_terms);

    // Adding a new term with weight < 0 implies that all root terms are
    // updated.
    $this->createTerm($this->vocabulary, ['weight' => -1]);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview', ['query' => ['page' => 2]]);
    $state->set('taxonomy_test_taxonomy_term_load', []);
    $this->submitForm([], 'Save');
    $loaded_terms = $state->get('taxonomy_test_taxonomy_term_load');
    $this->assertCount(12, $loaded_terms);
  }

}
