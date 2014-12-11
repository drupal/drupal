<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TermTranslationFieldViewTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests the translation of taxonomy terms field on nodes.
 *
 * @group taxonomy
 */
class TermTranslationFieldViewTest extends TaxonomyTestBase {

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary;
   */
  protected $vocabulary;

  /**
   * The term that should be translated.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term;

  /**
   * The field name for our taxonomy term field.
   *
   * @var string
   */
  protected $termFieldName = 'field_tag';

  /**
   * The langcode of the source language.
   *
   * @var string
   */
  protected $baseLangcode = 'en';

  /**
   * Target langcode for translation.
   *
   * @var string
   */
  protected $translateToLangcode = 'hu';

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
   * The node to check the translated value on.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

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
   * Adds additional languages.
   */
  protected function setupLanguages() {
    ConfigurableLanguage::createFromLangcode($this->translateToLangcode)->save();
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
   * Enables translations where it needed.
   */
  protected function enableTranslation() {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    \Drupal::service('content_translation.manager')->setEnabled('taxonomy_vocabulary', $this->vocabulary->id(), TRUE);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
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

  /**
   * Adds term reference field for the article content type.
   */
  protected function setUpTermReferenceField() {
    entity_create('field_storage_config', array(
      'field_name' => $this->termFieldName,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'translatable' => FALSE,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();

    $field = entity_create('field_config', array(
      'field_name' => $this->termFieldName,
      'bundle' => 'article',
      'entity_type' => 'node',
    ));
    $field->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->termFieldName, array(
        'type' => 'taxonomy_autocomplete',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->termFieldName, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
  }

}
