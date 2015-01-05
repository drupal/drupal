<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TermTranslationBreadcrumbTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\system\Tests\Menu\AssertBreadcrumbTrait;

/**
 * Tests for proper breadcrumb translation.
 *
 * @group taxonomy
 */
class TermTranslationBreadcrumbTest extends TaxonomyTestBase {

  use AssertBreadcrumbTrait;
  use TaxonomyTranslationTestTrait;

  /**
   * Term to translated term mapping.
   *
   * @var array
   */
  protected $termTranslationMap = array(
    'one' => 'translatedOne',
    'two' => 'translatedTwo',
    'three' => 'translatedThree',
  );

  /**
   * Created terms.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $terms = array();

  /**
   * {@inheritdoc}
   */
  public static $modules = array('taxonomy', 'language', 'content_translation');

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
    $breadcrumb = array('' => 'Home');
    foreach ($this->terms as $term) {
      $breadcrumb[$term->getSystemPath()] = $term->label();
    }
    // The last item will not be in the breadcrumb.
    array_pop($breadcrumb);

    // Check the breadcrumb on the leaf term page.
    $term = $this->getLeafTerm();
    $this->assertBreadcrumb($term->getSystemPath(), $breadcrumb, $term->label());

    // Construct the expected translated breadcrumb.
    $breadcrumb = array($this->translateToLangcode => 'Home');
    foreach ($this->terms as $term) {
      $translated = $term->getTranslation($this->translateToLangcode);
      $path = $this->translateToLangcode . '/' . $translated->getSystemPath();
      $breadcrumb[$path] = $translated->label();
    }
    array_pop($breadcrumb);

    // Check for the translated breadcrumb on the translated leaf term page.
    $term = $this->getLeafTerm();
    $translated = $term->getTranslation($this->translateToLangcode);
    $path = $this->translateToLangcode . '/' . $translated->getSystemPath();
    $this->assertBreadcrumb($path, $breadcrumb, $translated->label());
  }

  /**
   * Setup translated terms in a hierarchy.
   */
  protected function setUpTerms() {
    $parent_vid = 0;
    foreach ($this->termTranslationMap as $name => $translation) {

      $term = $this->createTerm($this->vocabulary, array(
        'name' => $name,
        'langcode' => $this->baseLangcode,
        'parent' => $parent_vid,
      ));

      $term->addTranslation($this->translateToLangcode, array(
        'name' => $translation,
      ));
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
