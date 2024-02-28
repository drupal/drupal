<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;

/**
 * Provides common testing base for translated taxonomy terms.
 */
trait TaxonomyTranslationTestTrait {

  use EntityReferenceFieldCreationTrait;

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
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
  }

  /**
   * Adds term reference field for the article content type.
   */
  protected function setUpTermReferenceField() {
    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $this->termFieldName, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_storage = FieldStorageConfig::loadByName('node', $this->termFieldName);
    $field_storage->setTranslatable(FALSE);
    $field_storage->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($this->termFieldName, [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($this->termFieldName, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

}
