<?php

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Url;
use Drupal\system\Tests\Menu\AssertBreadcrumbTrait;

/**
 * Tests for proper breadcrumb translation.
 *
 * @group taxonomy
 */
class TermTranslationTest extends TaxonomyTestBase {

  use AssertBreadcrumbTrait;
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
  public static $modules = ['taxonomy', 'language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupLanguages();
    $this->vocabulary = $this->createVocabulary();
    $this->enableTranslation();
    $this->setUpTerms();
    $this->setUpTermReferenceField();
  }

  /**
   * Test translated breadcrumbs.
   */
  public function testTranslatedBreadcrumbs() {
    // Ensure non-translated breadcrumb is correct.
    $breadcrumb = [Url::fromRoute('<front>')->toString() => 'Home'];
    foreach ($this->terms as $term) {
      $breadcrumb[$term->url()] = $term->label();
    }
    // The last item will not be in the breadcrumb.
    array_pop($breadcrumb);

    // Check the breadcrumb on the leaf term page.
    $term = $this->getLeafTerm();
    $this->assertBreadcrumb($term->urlInfo(), $breadcrumb, $term->label());

    $languages = \Drupal::languageManager()->getLanguages();

    // Construct the expected translated breadcrumb.
    $breadcrumb = [Url::fromRoute('<front>', [], ['language' => $languages[$this->translateToLangcode]])->toString() => 'Home'];
    foreach ($this->terms as $term) {
      $translated = $term->getTranslation($this->translateToLangcode);
      $url = $translated->url('canonical', ['language' => $languages[$this->translateToLangcode]]);
      $breadcrumb[$url] = $translated->label();
    }
    array_pop($breadcrumb);

    // Check for the translated breadcrumb on the translated leaf term page.
    $term = $this->getLeafTerm();
    $translated = $term->getTranslation($this->translateToLangcode);
    $this->assertBreadcrumb($translated->urlInfo('canonical', ['language' => $languages[$this->translateToLangcode]]), $breadcrumb, $translated->label());

  }

  /**
   * Test translation of terms are showed in the node.
   */
  public function testTermsTranslation() {

    // Set the display of the term reference field on the article content type
    // to "Check boxes/radio buttons".
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->termFieldName, [
        'type' => 'options_buttons',
      ])
      ->save();
    $this->drupalLogin($this->drupalCreateUser(['create article content']));

    // Test terms are listed.
    $this->drupalget('node/add/article');
    $this->assertText('one');
    $this->assertText('two');
    $this->assertText('three');

    // Test terms translated are listed.
    $this->drupalget('hu/node/add/article');
    $this->assertText('translatedOne');
    $this->assertText('translatedTwo');
    $this->assertText('translatedThree');
  }

  /**
   * Setup translated terms in a hierarchy.
   */
  protected function setUpTerms() {
    $parent_vid = 0;
    foreach ($this->termTranslationMap as $name => $translation) {

      $term = $this->createTerm($this->vocabulary, [
        'name' => $name,
        'langcode' => $this->baseLangcode,
        'parent' => $parent_vid,
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

  /**
   * Get the final (leaf) term in the hierarchy.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The final term in the hierarchy.
   */
  protected function getLeafTerm() {
    return $this->terms[count($this->termTranslationMap) - 1];
  }

}
