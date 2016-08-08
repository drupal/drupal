<?php

namespace Drupal\content_moderation;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * General service for moderation-related questions about Entity API.
 */
class ModerationInformation implements ModerationInformationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a new ModerationInformation instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratableEntity(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    return $this->isModeratableBundle($entity->getEntityType(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratableEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->hasHandlerClass('moderation');
  }

  /**
   * {@inheritdoc}
   */
  public function loadBundleEntity($bundle_entity_type_id, $bundle_id) {
    if ($bundle_entity_type_id) {
      return $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($bundle_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratableBundle(EntityTypeInterface $entity_type, $bundle) {
    if ($bundle_entity = $this->loadBundleEntity($entity_type->getBundleEntityType(), $bundle)) {
      return $bundle_entity->getThirdPartySetting('content_moderation', 'enabled', FALSE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function selectRevisionableEntityTypes(array $entity_types) {
    return array_filter($entity_types, function (EntityTypeInterface $type) use ($entity_types) {
      return ($type instanceof ConfigEntityTypeInterface)
      && ($bundle_of = $type->get('bundle_of'))
      && $entity_types[$bundle_of]->isRevisionable();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function selectRevisionableEntities(array $entity_types) {
    return array_filter($entity_types, function (EntityTypeInterface $type) use ($entity_types) {
      return ($type instanceof ContentEntityTypeInterface)
      && $type->isRevisionable()
      && $type->getBundleEntityType();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function isBundleForModeratableEntity(EntityInterface $entity) {
    $type = $entity->getEntityType();

    return
      $type instanceof ConfigEntityTypeInterface
      && ($bundle_of = $type->get('bundle_of'))
      && $this->entityTypeManager->getDefinition($bundle_of)->isRevisionable()
      && $this->currentUser->hasPermission('administer moderation states');
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntityForm(FormInterface $form_object) {
    return $form_object instanceof ContentEntityFormInterface
    && $this->isModeratableEntity($form_object->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionableBundleForm(FormInterface $form_object) {
    if ($form_object instanceof BundleEntityFormBase) {
      $bundle_of = $form_object->getEntity()->getEntityType()->getBundleOf();
      $type = $this->entityTypeManager->getDefinition($bundle_of);
      return $type->isRevisionable();
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
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityTypeManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityTypeManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
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
  public function hasForwardRevision(ContentEntityInterface $entity) {
    return $this->isModeratableEntity($entity)
      && !($this->getLatestRevisionId($entity->getEntityTypeId(), $entity->id()) == $this->getDefaultRevisionId($entity->getEntityTypeId(), $entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function isLiveRevision(ContentEntityInterface $entity) {
    return $this->isLatestRevision($entity)
      && $entity->isDefaultRevision()
      && $entity->moderation_state->entity
      && $entity->moderation_state->entity->isPublishedState();
  }

}
