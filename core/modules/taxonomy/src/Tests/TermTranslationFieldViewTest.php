<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TermTranslationFieldViewTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests the translation of taxonomy terms field on nodes.
 *
 * @group taxonomy
 */
class TermTranslationFieldViewTest extends TaxonomyTestBase {

  use TaxonomyTranslationTestTrait;

  /**
   * The term that should be translated.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term;

  /**
   * The tag in the source language.
   *
   * @var string
   */
  protected $baseTagName = 'OriginalTagName';

  /**
   * The translated value for the tag.
   *
   * @var string
   */
  protected $translatedTagName = 'TranslatedTagName';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'taxonomy');

  protected function setUp() {
    parent::setUp();
    $this->setupLanguages();
    $this->vocabulary = $this->createVocabulary();
    $this->enableTranslation();
    $this->setUpTerm();
    $this->setUpTermReferenceField();
    $this->setUpNode();
  }

  /**
   * Tests if the translated taxonomy term is displayed.
   */
  public function testTranslatedTaxonomyTermReferenceDisplay() {
    $path = 'node/' . $this->node->id();
    $translation_path = $this->translateToLangcode . '/' . $path;

    $this->drupalGet($path);
    $this->assertNoText($this->translatedTagName);
    $this->assertText($this->baseTagName);
    $this->drupalGet($translation_path);
    $this->assertText($this->translatedTagName);
    $this->assertNoText($this->baseTagName);
  }

  /**
   * Creates a test subject node, with translation.
   */
  protected function setUpNode() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = entity_create('node', array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'description' => array(
        'value' => $this->randomMachineName(),
        'format' => 'basic_html',
      ),
      $this->termFieldName => array(array('target_id' => $this->term->id())),
      'langcode' => $this->baseLangcode,
    ));
    $node->save();
    $node->addTranslation($this->translateToLangcode, array());
    $node->save();
    $this->node = $node;
  }

  /**
   * Creates a test subject term, with translation.
   */
  protected function setUpTerm() {
    $this->term = $this->createTerm($this->vocabulary, array(
      'name' => $this->baseTagName,
      'langcode' => $this->baseLangcode,
    ));

    $this->term->addTranslation($this->translateToLangcode, array(
      'name' => $this->translatedTagName,
    ));
    $this->term->save();
  }

}
