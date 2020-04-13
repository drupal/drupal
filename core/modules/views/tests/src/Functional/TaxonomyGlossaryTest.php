<?php

namespace Drupal\Tests\views\Functional;

use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests glossary functionality of taxonomy views.
 *
 * @group views
 */
class TaxonomyGlossaryTest extends ViewTestBase {

  use TaxonomyTestTrait;

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
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_taxonomy_glossary'];

  /**
   * Taxonomy terms used by this test.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $taxonomyTerms;

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->createVocabulary();
    for ($i = 0; $i < 10; $i++) {
      $this->taxonomyTerms[] = $this->createTerm($vocabulary);
    }
  }

  /**
   * Tests a taxonomy glossary view.
   */
  public function testTaxonomyGlossaryView() {
    // Go the taxonomy glossary page for the first term.
    $this->drupalGet('test_taxonomy_glossary/' . substr($this->taxonomyTerms[0]->getName(), 0, 1));
    $this->assertText($this->taxonomyTerms[0]->getName());
  }

}
