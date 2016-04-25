<?php

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides common functionality for content translation.
 */
class ContentTranslationManager implements ContentTranslationManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The updates manager.
   *
   * @var \Drupal\content_translation\ContentTranslationUpdatesManager
   */
  protected $updatesManager;

  /**
   * Constructs a ContentTranslationManageAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\content_translation\ContentTranslationUpdatesManager $updates_manager
   *   The updates manager.
   */
  public function __construct(EntityManagerInterface $manager, ContentTranslationUpdatesManager $updates_manager) {
    $this->entityManager = $manager;
    $this->updatesManager = $updates_manager;
  }

  /**
   * {@inheritdoc}
   */
  function getTranslationHandler($entity_type_id) {
    return $this->entityManager->getHandler($entity_type_id, 'translation');
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationMetadata(EntityInterface $translation) {
    // We need a new instance of the metadata handler wrapping each translation.
    $entity_type = $translation->getEntityType();
    $class = $entity_type->get('content_translation_metadata');
    return new $class($translation, $this->getTranslationHandler($entity_type->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported($entity_type_id) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    return $entity_type->isTranslatable() && ($entity_type->hasLinkTemplate('drupal:content-translation-overview') || $entity_type->get('content_translation_ui_skip'));
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    $supported_types = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($this->isSupported($entity_type_id)) {
        $supported_types[$entity_type_id] = $entity_type;
      }
    }
    return $supported_types;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($entity_type_id, $bundle, $value) {
    $config = $this->loadContentLanguageSettings($entity_type_id, $bundle);
    $config->setThirdPartySetting('content_translation', 'enabled', $value)->save();
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $this->updatesManager->updateDefinitions(array($entity_type_id => $entity_type));
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled($entity_type_id, $bundle = NULL) {
    $enabled = FALSE;

    if ($this->isSupported($entity_type_id)) {
      $bundles = !empty($bundle) ? array($bundle) : array_keys($this->entityManager->getBundleInfo($entity_type_id));
      foreach ($bundles as $bundle) {
        $config = $this->loadContentLanguageSettings($entity_type_id, $bundle);
        if ($config->getThirdPartySetting('content_translation', 'enabled', FALSE)) {
          $enabled = TRUE;
          break;
        }
      }
    }

    return $enabled;
  }

  /**
   * Loads a content language config entity based on the entity type and bundle.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return \Drupal\language\Entity\ContentLanguageSettings
   *   The content language config entity if one exists. Otherwise, returns
   *   default values.
   */
  protected function loadContentLanguageSettings($entity_type_id, $bundle) {
    if ($entity_type_id == NULL || $bundle == NULL) {
      return NULL;
    }
    $config = $this->entityManager->getStorage('language_content_settings')->load($entity_type_id . '.' . $bundle);
    if ($config == NULL) {
      $config = $this->entityManager->getStorage('language_content_settings')->create(['target_entity_type_id' => $entity_type_id, 'target_bundle' => $bundle]);
    }
    return $config;
  }

}
