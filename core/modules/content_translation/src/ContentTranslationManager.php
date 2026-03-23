<?php

namespace Drupal\content_translation;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides common functionality for content translation.
 */
class ContentTranslationManager implements ContentTranslationManagerInterface, BundleTranslationSettingsInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected ?AccountProxyInterface $currentUser = NULL,
    protected ?LanguageManagerInterface $languageManager = NULL,
  ) {
    if (!$this->currentUser) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $currentUser argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3567484', E_USER_DEPRECATED);
      $this->currentUser = \Drupal::currentUser();
    }
    if (!$this->languageManager) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $languageManager argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3567484', E_USER_DEPRECATED);
      $this->languageManager = \Drupal::languageManager();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationHandler($entity_type_id) {
    return $this->entityTypeManager->getHandler($entity_type_id, 'translation');
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
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $entity_type->isTranslatable() && ($entity_type->hasLinkTemplate('drupal:content-translation-overview') || $entity_type->get('content_translation_ui_skip'));
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    $supported_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
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
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled($entity_type_id, $bundle = NULL) {
    $enabled = FALSE;

    if ($this->isSupported($entity_type_id)) {
      $bundles = !empty($bundle) ? [$bundle] : array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
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
   * {@inheritdoc}
   */
  public function setBundleTranslationSettings($entity_type_id, $bundle, array $settings) {
    $config = $this->loadContentLanguageSettings($entity_type_id, $bundle);
    $config->setThirdPartySetting('content_translation', 'bundle_settings', $settings)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleTranslationSettings($entity_type_id, $bundle) {
    $config = $this->loadContentLanguageSettings($entity_type_id, $bundle);
    return $config->getThirdPartySetting('content_translation', 'bundle_settings', []);
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): AccessResultInterface {
    $condition = $entity instanceof ContentEntityInterface
      && $entity->access('view')
      && !$entity->getUntranslated()->language()->isLocked()
      && $this->languageManager->isMultilingual()
      && $entity->isTranslatable()
      && (
        $this->currentUser->hasPermission('create content translations')
        || $this->currentUser->hasPermission('update content translations')
        || $this->currentUser->hasPermission('delete content translations')
        || (
          $this->currentUser->hasPermission('translate editable entities')
          && $entity->access('update')
        )
      );

    return AccessResult::allowedIf($condition)
      ->cachePerPermissions()
      ->addCacheableDependency($entity);
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
    $config = $this->entityTypeManager->getStorage('language_content_settings')->load($entity_type_id . '.' . $bundle);
    if ($config == NULL) {
      $config = $this->entityTypeManager->getStorage('language_content_settings')->create(['target_entity_type_id' => $entity_type_id, 'target_bundle' => $bundle]);
    }
    return $config;
  }

  /**
   * Checks whether support for pending revisions should be enabled.
   *
   * @param string $entity_type_id
   *   The ID of the entity type to be checked.
   * @param string $bundle_id
   *   (optional) The ID of the bundle to be checked. Defaults to none.
   *
   * @return bool
   *   TRUE if pending revisions should be enabled, FALSE otherwise.
   *
   * @internal
   *   There is ongoing discussion about how pending revisions should behave.
   *   The logic enabling pending revision support is likely to change once a
   *   decision is made.
   *
   * @see https://www.drupal.org/node/2940575
   */
  public static function isPendingRevisionSupportEnabled($entity_type_id, $bundle_id = NULL) {
    if (!\Drupal::moduleHandler()->moduleExists('content_moderation')) {
      return FALSE;
    }

    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    if ($bundle_id) {
      return \Drupal::service('content_moderation.moderation_information')->shouldModerateEntitiesOfBundle($entity_type, $bundle_id);
    }
    else {
      return \Drupal::service('content_moderation.moderation_information')->canModerateEntitiesOfEntityType($entity_type);
    }
  }

}
