<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceFieldTranslatedReferenceViewTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the translation of entity reference field display on nodes.
 *
 * @group entity_reference
 */
class EntityReferenceFieldTranslatedReferenceViewTest extends WebTestBase {

  /**
   * Flag indicating whether the field is translatable.
   *
   * @var bool
   */
  protected $translatable = TRUE;

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
   * The test entity type name.
   *
   * @var string
   */
  protected $testEntityTypeName = 'node';

  /**
   * Entity type which have the entity reference field.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $referrerType;

  /**
   * Entity type which can be referenced.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $referencedType;

  /**
   * The referrer entity.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $referrerEntity;

  /**
   * The entity to refer.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $referencedEntityWithoutTranslation;

  /**
   * The entity to refer.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $referencedEntityWithTranslation;

  /**
   * The machine name of the entity reference field.
   *
   * @var string
   */
  protected $referenceFieldName = 'test_reference_field';

  /**
   * The label of the untranslated referenced entity, used in assertions.
   *
   * @var string
   */
  protected $labelOfNotTranslatedReference;

  /**
   * The original label of the referenced entity, used in assertions.
   *
   * @var string
   */
  protected $originalLabel;

  /**
   * The translated label of the referenced entity, used in assertions.
   *
   * @var string
   */
  protected $translatedLabel;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'language',
    'content_translation',
    'entity_reference',
    'node',
  );

  protected function setUp() {
    parent::setUp();

    $this->labelOfNotTranslatedReference = $this->randomMachineName();
    $this->originalLabel = $this->randomMachineName();
    $this->translatedLabel = $this->randomMachineName();

    $this->setUpLanguages();

    // We setup languages, so we need to ensure that the language manager
    // and language path processor is updated.
    $this->rebuildContainer();

    $this->setUpContentTypes();
    $this->enableTranslation();
    $this->setUpEntityReferenceField();
    $this->createContent();
  }

  /**
   * Tests if the translated entity is displayed in an entity reference field.
   */
  public function testTranslatedEntityReferenceDisplay() {
    $url = $this->referrerEntity->urlInfo();
    $translation_url = $this->referrerEntity->urlInfo('canonical', ['language' => ConfigurableLanguage::load($this->translateToLangcode)]);

    $this->drupalGet($url);
    $this->assertText($this->labelOfNotTranslatedReference, 'The label of not translated reference is displayed.');
    $this->assertText($this->originalLabel, 'The default label of translated reference is displayed.');
    $this->assertNoText($this->translatedLabel, 'The translated label of translated reference is not displayed.');
    $this->drupalGet($translation_url);
    $this->assertText($this->labelOfNotTranslatedReference, 'The label of not translated reference is displayed.');
    $this->assertNoText($this->originalLabel, 'The default label of translated reference is not displayed.');
    $this->assertText($this->translatedLabel, 'The translated label of translated reference is displayed.');
  }

  /**
   * Adds additional languages.
   */
  protected function setUpLanguages() {
    ConfigurableLanguage::createFromLangcode($this->translateToLangcode)->save();
  }

  /**
   * Creates a test subject contents, with translation.
   */
  protected function createContent() {
    $this->referencedEntityWithTranslation = $this->createReferencedEntityWithTranslation();
    $this->referencedEntityWithoutTranslation = $this->createNotTranslatedReferencedEntity();
    $this->referrerEntity = $this->createReferrerEntity();
  }

  /**
   * Enables translations where it needed.
   */
  protected function enableTranslation() {
    // Enable translation for the entity types and ensure the change is picked
    // up.
    \Drupal::service('content_translation.manager')->setEnabled($this->testEntityTypeName, $this->referrerType->id(), TRUE);
    \Drupal::service('content_translation.manager')->setEnabled($this->testEntityTypeName, $this->referencedType->id(), TRUE);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

  /**
   * Adds term reference field for the article content type.
   */
  protected function setUpEntityReferenceField() {
    entity_create('field_storage_config', array(
      'field_name' => $this->referenceFieldName,
      'entity_type' => $this->testEntityTypeName,
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'translatable' => $this->translatable,
      'settings' => array(
        'allowed_values' => array(
          array(
            'target_type' => $this->testEntityTypeName,
          ),
        ),
      ),
    ))->save();

    entity_create('field_config', array(
      'field_name' => $this->referenceFieldName,
      'bundle' => $this->referrerType->id(),
      'entity_type' => $this->testEntityTypeName,
    ))
    ->save();
    entity_get_form_display($this->testEntityTypeName, $this->referrerType->id(), 'default')
      ->setComponent($this->referenceFieldName, array(
        'type' => 'entity_reference_autocomplete',
      ))
      ->save();
    entity_get_display($this->testEntityTypeName, $this->referrerType->id(), 'default')
      ->setComponent($this->referenceFieldName, array(
        'type' => 'entity_reference_label',
      ))
      ->save();
  }

  /**
   * Create content types.
   */
  protected function setUpContentTypes() {
    $this->referrerType = $this->drupalCreateContentType(array(
        'type' => 'referrer',
        'name' => 'Referrer',
      ));
    $this->referencedType = $this->drupalCreateContentType(array(
        'type' => 'referenced_page',
        'name' => 'Referenced Page',
      ));
  }

  /**
   * Create a referenced entity with a translation.
   */
  protected function createReferencedEntityWithTranslation() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = entity_create($this->testEntityTypeName, array(
      'title' => $this->originalLabel,
      'type' => $this->referencedType->id(),
      'description' => array(
        'value' => $this->randomMachineName(),
        'format' => 'basic_html',
      ),
      'langcode' => $this->baseLangcode,
    ));
    $node->save();
    $node->addTranslation($this->translateToLangcode, array(
      'title' => $this->translatedLabel,
    ));
    $node->save();

    return $node;
  }


  /**
   * Create the referenced entity.
   */
  protected function createNotTranslatedReferencedEntity() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = entity_create($this->testEntityTypeName, array(
      'title' => $this->labelOfNotTranslatedReference,
      'type' => $this->referencedType->id(),
      'description' => array(
        'value' => $this->randomMachineName(),
        'format' => 'basic_html',
      ),
      'langcode' => $this->baseLangcode,
    ));
    $node->save();

    return $node;
  }

  /**
   * Create the referrer entity.
   */
  protected function createReferrerEntity() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = entity_create($this->testEntityTypeName, array(
      'title' => $this->randomMachineName(),
      'type' => $this->referrerType->id(),
      'description' => array(
        'value' => $this->randomMachineName(),
        'format' => 'basic_html',
      ),
      $this->referenceFieldName => array(
        array('target_id' => $this->referencedEntityWithTranslation->id()),
        array('target_id' => $this->referencedEntityWithoutTranslation->id()),
      ),
      'langcode' => $this->baseLangcode,
    ));
    $node->save();
    $node->addTranslation($this->translateToLangcode, $node->toArray());
    $node->save();

    return $node;
  }

}
