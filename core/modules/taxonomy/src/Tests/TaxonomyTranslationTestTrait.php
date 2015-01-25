<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyTranslationTestTrait.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Provides common testing base for translated taxonomy terms.
 */
trait TaxonomyTranslationTestTrait {

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
