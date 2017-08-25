<?php

namespace Drupal\content_moderation;

use Drupal\content_moderation\Entity\ContentModerationState as ContentModerationStateEntity;
use Drupal\content_moderation\Entity\ContentModerationStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\content_moderation\Form\EntityModerationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events.
 *
 * @internal
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Moderation Information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Form Builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity bundle information service.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, EntityTypeBundleInfoInterface $bundle_info) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @see hook_entity_presave()
   */
  public function entityPresave(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }

    if ($entity->moderation_state->value) {
      $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
      /** @var \Drupal\content_moderation\ContentModerationState $current_state */
      $current_state = $workflow->getTypePlugin()
        ->getState($entity->moderation_state->value);

      // This entity is default if it is new, a new translation, the default
      // revision, or the default revision is not published.
      $update_default_revision = $entity->isNew()
        || $entity->isNewTranslation()
        || $current_state->isDefaultRevisionState()
        || !$this->moderationInfo->isDefaultRevisionPublished($entity);

      // Fire per-entity-type logic for handling the save process.
      $this->entityTypeManager
        ->getHandler($entity->getEntityTypeId(), 'moderation')
        ->onPresave($entity, $update_default_revision, $current_state->isPublishedState());
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_insert()
   */
  public function entityInsert(EntityInterface $entity) {
    if ($this->moderationInfo->isModeratedEntity($entity)) {
      $this->updateOrCreateFromEntity($entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_update()
   */
  public function entityUpdate(EntityInterface $entity) {
    if ($this->moderationInfo->isModeratedEntity($entity)) {
      $this->updateOrCreateFromEntity($entity);
    }
  }

  /**
   * Creates or updates the moderation state of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update or create a moderation state for.
   */
  protected function updateOrCreateFromEntity(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity_revision_id = $entity->getRevisionId();
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    $content_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($entity);

    if (!($content_moderation_state instanceof ContentModerationStateInterface)) {
      $storage = $this->entityTypeManager->getStorage('content_moderation_state');
      $content_moderation_state = $storage->create([
        'content_entity_type_id' => $entity->getEntityTypeId(),
        'content_entity_id' => $entity->id(),
        // Make sure that the moderation state entity has the same language code
        // as the moderated entity.
        'langcode' => $entity->language()->getId(),
      ]);
      $content_moderation_state->workflow->target_id = $workflow->id();
    }
    elseif ($content_moderation_state->content_entity_revision_id->value != $entity_revision_id) {
      // If a new revision of the content has been created, add a new content
      // moderation state revision.
      $content_moderation_state->setNewRevision(TRUE);
    }

    // Sync translations.
    if ($entity->getEntityType()->hasKey('langcode')) {
      $entity_langcode = $entity->language()->getId();
      if (!$content_moderation_state->hasTranslation($entity_langcode)) {
        $content_moderation_state->addTranslation($entity_langcode);
      }
      if ($content_moderation_state->language()->getId() !== $entity_langcode) {
        $content_moderation_state = $content_moderation_state->getTranslation($entity_langcode);
      }
    }

    // Create the ContentModerationState entity for the inserted entity.
    $moderation_state = $entity->moderation_state->value;
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$moderation_state) {
      $moderation_state = $workflow->getTypePlugin()->getInitialState($entity)->id();
    }

    // @todo what if $entity->moderation_state is null at this point?
    $content_moderation_state->set('content_entity_revision_id', $entity_revision_id);
    $content_moderation_state->set('moderation_state', $moderation_state);
    ContentModerationStateEntity::updateOrCreateFromEntity($content_moderation_state);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   *
   * @see hook_entity_delete()
   */
  public function entityDelete(EntityInterface $entity) {
    $content_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($entity);
    if ($content_moderation_state) {
      $content_moderation_state->delete();
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity revision being deleted.
   *
   * @see hook_entity_revision_delete()
   */
  public function entityRevisionDelete(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$entity->isDefaultRevision()) {
      $content_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($entity);
      if ($content_moderation_state) {
        $this->entityTypeManager
          ->getStorage('content_moderation_state')
          ->deleteRevision($content_moderation_state->getRevisionId());
      }
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $translation
   *   The entity translation being deleted.
   *
   * @see hook_entity_translation_delete()
   */
  public function entityTranslationDelete(EntityInterface $translation) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
    if (!$translation->isDefaultTranslation()) {
      $langcode = $translation->language()->getId();
      $content_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($translation);
      if ($content_moderation_state && $content_moderation_state->hasTranslation($langcode)) {
        $content_moderation_state->removeTranslation($langcode);
        ContentModerationStateEntity::updateOrCreateFromEntity($content_moderation_state);
      }
    }
  }

  /**
   * Act on entities being assembled before rendering.
   *
   * @see hook_entity_view()
   * @see EntityFieldManagerInterface::getExtraFields()
   */
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }
    if (!$this->moderationInfo->isLatestRevision($entity)) {
      return;
    }
    if ($this->moderationInfo->isLiveRevision($entity)) {
      return;
    }
    // Don't display the moderation form when when:
    // - The revision is not translation affected.
    // - There are more than one translation languages.
    // - The entity has pending revisions.
    if (!$this->moderationInfo->isPendingRevisionAllowed($entity)) {
      return;
    }

    $component = $display->getComponent('content_moderation_control');
    if ($component) {
      $build['content_moderation_control'] = $this->formBuilder->getForm(EntityModerationForm::class, $entity);
    }
  }

}
