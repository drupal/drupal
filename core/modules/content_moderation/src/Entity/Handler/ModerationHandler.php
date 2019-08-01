<?php

namespace Drupal\content_moderation\Entity\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common customizations for most/all entities.
 *
 * This class is intended primarily as a base class.
 *
 * @internal
 */
class ModerationHandler implements ModerationHandlerInterface, EntityHandlerInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $default_revision, $published_state) {
    // When entities are syncing, content moderation should not force a new
    // revision to be created and should not update the default status of a
    // revision. This is useful if changes are being made to entities or
    // revisions which are not part of editorial updates triggered by normal
    // content changes.
    if (!$entity->isSyncing()) {
      $entity->setNewRevision(TRUE);
      $entity->isDefaultRevision($default_revision);
    }

    // Update publishing status if it can be updated and if it needs updating.
    if (($entity instanceof EntityPublishedInterface) && $entity->isPublished() !== $published_state) {
      $published_state ? $entity->setPublished() : $entity->setUnpublished();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
  }

}
