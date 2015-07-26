<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyTranslationTestTrait.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_reference\Tests\EntityReferenceTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Provides common testing base for translated taxonomy terms.
 */
trait TaxonomyTranslationTestTrait {

  use EntityReferenceTestTrait;

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary;
   */
  protected $vocabulary;

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
   * The node to check the translated value on.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Adds additional languages.
   */
  protected function setupLanguages() {
    ConfigurableLanguage::createFromLangcode($this->translateToLangcode)->save();
    $this->rebuildContainer();
  }

  /**
   * Enables translations where it needed.
   */
  protected function enableTranslation() {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    \Drupal::service('content_translation.manager')->setEnabled('taxonomy_term', $this->vocabulary->id(), TRUE);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

  /**
   * Adds term reference field for the article content type.
   *
   * @param bool $translatable
   *   (optional) If TRUE, create a translatable term reference field. Defaults
   *   to FALSE.
   */
  protected function setUpTermReferenceField($translatable = FALSE) {
    $handler_settings = array(
      'target_bundles' => array(
        $this->vocabulary->id() => $this->vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('node', 'article', $this->termFieldName, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_storage = FieldStorageConfig::loadByName('node', $this->termFieldName);
    $field_storage->setTranslatable($translatable);
    $field_storage->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->termFieldName, array(
        'type' => 'entity_reference_autocomplete_tags',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->termFieldName, array(
        'type' => 'entity_reference_label',
      ))
      ->save();
  }

}
