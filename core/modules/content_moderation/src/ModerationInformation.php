<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * General service for moderation-related questions about Entity API.
 */
class ModerationInformation implements ModerationInformationInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Creates a new ModerationInformation instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntity(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    return $this->shouldModerateEntitiesOfBundle($entity->getEntityType(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntityType(EntityTypeInterface $entity_type) {
    $bundles = $this->bundleInfo->getBundleInfo($entity_type->id());
    return !empty(array_column($bundles, 'workflow'));
  }

  /**
   * {@inheritdoc}
   */
  public function canModerateEntitiesOfEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->hasHandlerClass('moderation');
  }

  /**
   * {@inheritdoc}
   */
  public function shouldModerateEntitiesOfBundle(EntityTypeInterface $entity_type, $bundle) {
    if ($this->canModerateEntitiesOfEntityType($entity_type)) {
      $bundles = $this->bundleInfo->getBundleInfo($entity_type->id());
      return isset($bundles[$bundle]['workflow']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision($entity_type_id, $entity_id) {
    if ($latest_revision_id = $this->getLatestRevisionId($entity_type_id, $entity_id)) {
      return $this->entityTypeManager->getStorage($entity_type_id)->loadRevision($latest_revision_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $result = $storage->getQuery()
        ->latestRevision()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        // No access check is performed here since this is an API function and
        // should return the same ID regardless of the current user.
        ->accessCheck(FALSE)
        ->execute();
      if ($result) {
        return key($result);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $result = $storage->getQuery()
        ->currentRevision()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        // No access check is performed here since this is an API function and
        // should return the same ID regardless of the current user.
        ->accessCheck(FALSE)
        ->execute();
      if ($result) {
        return key($result);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAffectedRevisionTranslation(ContentEntityInterface $entity) {
    foreach ($entity->getTranslationLanguages() as $language) {
      $translation = $entity->getTranslation($language->getId());
      if (!$translation->isDefaultRevision() && $translation->isRevisionTranslationAffected()) {
        return $translation;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision(ContentEntityInterface $entity) {
    return $entity->getRevisionId() == $this->getLatestRevisionId($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function hasPendingRevision(ContentEntityInterface $entity) {
    $result = FALSE;
    if ($this->isModeratedEntity($entity)) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId());
      $default_revision_id = $entity->isDefaultRevision() && !$entity->isNewRevision() && ($revision_id = $entity->getRevisionId()) ?
        $revision_id : $this->getDefaultRevisionId($entity->getEntityTypeId(), $entity->id());
      if ($latest_revision_id != $default_revision_id) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $latest_revision */
        $latest_revision = $storage->loadRevision($latest_revision_id);
        $result = !$latest_revision->wasDefaultRevision();
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function isLiveRevision(ContentEntityInterface $entity) {
    $workflow = $this->getWorkflowForEntity($entity);
    return $this->isLatestRevision($entity)
      && $entity->isDefaultRevision()
      && $entity->moderation_state->value
      && $workflow->getTypePlugin()->getState($entity->moderation_state->value)->isPublishedState();
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevisionPublished(ContentEntityInterface $entity) {
    $workflow = $this->getWorkflowForEntity($entity);
    $default_revision = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->load($entity->id());
    // If no default revision could be loaded, the entity has not yet been
    // saved. In this case the moderation_state of the unsaved entity can be
    // used, since once saved it will become the default.
    $default_revision = $default_revision  ?: $entity;

    // Ensure we are checking all translations of the default revision.
    if ($default_revision instanceof TranslatableInterface && $default_revision->isTranslatable()) {
      // Loop through each language that has a translation.
      foreach ($default_revision->getTranslationLanguages() as $language) {
        // Load the translated revision.
        $translation = $default_revision->getTranslation($language->getId());
        // If the moderation state is empty, it was not stored yet so no point
        // in doing further work.
        $moderation_state = $translation->moderation_state->value;
        if (!$moderation_state) {
          continue;
        }
        // Return TRUE if a translation with a published state is found.
        if ($workflow->getTypePlugin()->getState($moderation_state)->isPublishedState()) {
          return TRUE;
        }
      }
    }

    return $workflow->getTypePlugin()->getState($default_revision->moderation_state->value)->isPublishedState();
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowForEntity(ContentEntityInterface $entity) {
    return $this->getWorkflowForEntityTypeAndBundle($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowForEntityTypeAndBundle($entity_type_id, $bundle_id) {
    $bundles = $this->bundleInfo->getBundleInfo($entity_type_id);
    if (isset($bundles[$bundle_id]['workflow'])) {
      return $this->entityTypeManager->getStorage('workflow')->load($bundles[$bundle_id]['workflow']);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnsupportedFeatures(EntityTypeInterface $entity_type) {
    $features = [];
    // Test if entity is publishable.
    if (!$entity_type->entityClassImplements(EntityPublishedInterface::class)) {
      $features['publishing'] = $this->t("@entity_type_plural_label do not support publishing statuses. For example, even after transitioning from a published workflow state to an unpublished workflow state they will still be visible to site visitors.", ['@entity_type_plural_label' => $entity_type->getCollectionLabel()]);
    }
    return $features;
  }

}
