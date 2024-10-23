<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\node\Entity\Node;

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
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'content_translation', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
  public function testTranslatedTaxonomyTermReferenceDisplay(): void {
    $path = 'node/' . $this->node->id();
    $translation_path = $this->translateToLangcode . '/' . $path;

    $this->drupalGet($path);
    $this->assertSession()->pageTextNotContains($this->translatedTagName);
    $this->assertSession()->pageTextContains($this->baseTagName);
    $this->drupalGet($translation_path);
    $this->assertSession()->pageTextContains($this->translatedTagName);
    $this->assertSession()->pageTextNotContains($this->baseTagName);
  }

  /**
   * Creates a test subject node, with translation.
   */
  protected function setUpNode(): void {
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'description' => [
        [
          'value' => $this->randomMachineName(),
          'format' => 'basic_html',
        ],
      ],
      $this->termFieldName => [['target_id' => $this->term->id()]],
      'langcode' => $this->baseLangcode,
    ]);
    $node->save();
    $node->addTranslation($this->translateToLangcode, $node->toArray());
    $node->save();
    $this->node = $node;
  }

  /**
   * Creates a test subject term, with translation.
   */
  protected function setUpTerm(): void {
    $this->term = $this->createTerm($this->vocabulary, [
      'name' => $this->baseTagName,
      'langcode' => $this->baseLangcode,
    ]);

    $this->term->addTranslation($this->translateToLangcode, [
      'name' => $this->translatedTagName,
    ]);
    $this->term->save();
  }

}
