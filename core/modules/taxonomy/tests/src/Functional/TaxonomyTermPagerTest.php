<?php

namespace Drupal\Tests\taxonomy\Functional;

/**
 * Ensures that the term pager works properly.
 *
 * @group taxonomy
 */
class TaxonomyTermPagerTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy'];

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
  public function testTaxonomyTermOverviewPager() {
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

}
