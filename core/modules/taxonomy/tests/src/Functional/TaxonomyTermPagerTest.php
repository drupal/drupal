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
    $this->assertSession()->responseNotMatches('|<nav class="pager" [^>]*>|', 'Pager is not visible on page 1');

    // Create 3 more terms to show pager.
    for ($x = 1; $x <= 3; $x++) {
      $this->createTerm($this->vocabulary);
    }

    // Get Page 1.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertPattern('|<nav class="pager" [^>]*>|', 'Pager is visible on page 1');

    // Get Page 2.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview', ['query' => ['page' => 1]]);
    $this->assertPattern('|<nav class="pager" [^>]*>|', 'Pager is visible on page 2');
  }

}
