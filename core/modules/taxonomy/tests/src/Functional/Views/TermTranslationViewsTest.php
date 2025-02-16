<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\Core\Url;
use Drupal\Tests\taxonomy\Functional\TaxonomyTranslationTestTrait;

/**
 * Tests for views translation.
 *
 * @group taxonomy
 */
class TermTranslationViewsTest extends TaxonomyTestBase {

  use TaxonomyTranslationTestTrait;

  /**
   * Term to translated term mapping.
   *
   * @var array
   */
  protected $termTranslationMap = [
    'one' => 'translatedOne',
    'two' => 'translatedTwo',
    'three' => 'translatedThree',
  ];

  /**
   * Created terms.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'language', 'content_translation', 'views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['taxonomy_translated_term_name_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Language object.
   *
   * @var \Drupal\Core\Language\LanguageInterface|null
   */
  protected $translationLanguage;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);
    $this->setupLanguages();
    $this->enableTranslation();
    $this->setUpTerms();
    $this->translationLanguage = \Drupal::languageManager()->getLanguage($this->translateToLangcode);
  }

  /**
   * Ensure that proper translation is returned when contextual filter.
   *
   * Taxonomy term: Term ID & Content: Has taxonomy term ID (with depth)
   * contextual filters are enabled for two separate view modes.
   */
  public function testTermsTranslationWithContextualFilter(): void {
    $this->drupalLogin($this->rootUser);

    foreach ($this->terms as $term) {
      // Test with "Content: Has taxonomy term ID (with depth)" contextual
      // filter. Generate base language url and send request.
      $url = Url::fromRoute('view.taxonomy_translated_term_name_test.page_1', ['arg_0' => $term->id()])->toString();
      $this->drupalGet($url);
      $this->assertSession()->pageTextContains($term->label());

      // Generate translation URL and send request.
      $url = Url::fromRoute('view.taxonomy_translated_term_name_test.page_1', ['arg_0' => $term->id()], ['language' => $this->translationLanguage])->toString();
      $this->drupalGet($url);
      $this->assertSession()->pageTextContains($this->termTranslationMap[$term->label()]);

      // Test with "Taxonomy term: Term ID" contextual filter.
      // Generate base language url and send request.
      $url = Url::fromRoute('view.taxonomy_translated_term_name_test.page_2', ['arg_0' => $term->id()])->toString();
      $this->drupalGet($url);
      $this->assertSession()->pageTextContains($term->label());

      // Generate translation URL and send request.
      $url = Url::fromRoute('view.taxonomy_translated_term_name_test.page_2', ['arg_0' => $term->id()], ['language' => $this->translationLanguage])->toString();
      $this->drupalGet($url);
      $this->assertSession()->pageTextContains($this->termTranslationMap[$term->label()]);
    }
  }

  /**
   * Setup translated terms in a hierarchy.
   */
  protected function setUpTerms(): void {
    $parent_vid = 0;
    foreach ($this->termTranslationMap as $name => $translation) {

      $term = $this->createTerm([
        'name' => $name,
        'langcode' => $this->baseLangcode,
        'parent' => $parent_vid,
        'vid' => $this->vocabulary->id(),
      ]);

      $term->addTranslation($this->translateToLangcode, [
        'name' => $translation,
      ]);
      $term->save();

      // Each term is nested under the last.
      $parent_vid = $term->id();

      $this->terms[] = $term;
    }
  }

}
