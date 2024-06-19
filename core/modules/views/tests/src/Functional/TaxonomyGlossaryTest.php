<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use Drupal\Core\Url;
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

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->createVocabulary();
    for ($i = 0; $i < 10; $i++) {
      $this->taxonomyTerms[] = $this->createTerm($vocabulary);
    }
    $this->taxonomyTerms[] = $this->createTerm($vocabulary, ['name' => '0' . $this->randomMachineName()]);
  }

  /**
   * Tests a taxonomy glossary view.
   */
  public function testTaxonomyGlossaryView(): void {
    $initials = [];
    foreach ($this->taxonomyTerms as $term) {
      $char = mb_strtolower(substr($term->label(), 0, 1));
      $initials += [$char => 0];
      $initials[$char]++;
    }

    $this->drupalGet('test_taxonomy_glossary');
    $assert_session = $this->assertSession();

    foreach ($initials as $char => $count) {
      $href = Url::fromUserInput('/test_taxonomy_glossary/' . $char)->toString();

      $xpath = $assert_session->buildXPathQuery('//a[@href=:href and normalize-space(text())=:label]', [
        ':href' => $href,
        ':label' => $char,
      ]);
      $link = $assert_session->elementExists('xpath', $xpath);

      // Assert that the expected number of results is indicated in the link.
      preg_match("/{$char} \(([0-9]+)\)/", $link->getParent()->getText(), $matches);
      $this->assertEquals($count, $matches[1]);
    }

    // Check that no other glossary links but the expected ones have been
    // rendered.
    $assert_session->elementsCount('xpath', '/ancestor::ul//a', count($initials), $link);

    // Go the taxonomy glossary page for the first term.
    $this->drupalGet('test_taxonomy_glossary/' . substr($this->taxonomyTerms[0]->getName(), 0, 1));
    $assert_session->pageTextContains($this->taxonomyTerms[0]->getName());
  }

}
