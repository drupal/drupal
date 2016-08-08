<?php

namespace Drupal\content_moderation\Entity\Handler;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common customizations for most/all entities.
 *
 * This class is intended primarily as a base class.
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
    // This is probably not necessary if configuration is setup correctly.
    $entity->setNewRevision(TRUE);
    $entity->isDefaultRevision($default_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleModerationConfigurationFormSubmit(ConfigEntityInterface $bundle) {
    // The Revisions portion of Entity API is not uniformly applied or
    // consistent. Until that's fixed, we'll make a best-attempt to apply it to
    // the common entity patterns so as to avoid every entity type needing to
    // implement this method, although some will still need to do so for now.
    // This is the API that should be universal, but isn't yet.
    // @see \Drupal\node\Entity\NodeType
    if (method_exists($bundle, 'setNewRevision')) {
      $bundle->setNewRevision(TRUE);
    }
    // This is the raw property used by NodeType, and likely others.
    elseif ($bundle->get('new_revision') !== NULL) {
      $bundle->set('new_revision', TRUE);
    }
    // This is the raw property used by BlockContentType, and maybe others.
    elseif ($bundle->get('revision') !== NULL) {
      $bundle->set('revision', TRUE);
    }

    $bundle->save();
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
