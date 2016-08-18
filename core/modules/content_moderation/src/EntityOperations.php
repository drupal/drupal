<?php

namespace Drupal\content_moderation;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\content_moderation\Form\EntityModerationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events.
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
   * The Revision Tracker service.
   *
   * @var \Drupal\content_moderation\RevisionTrackerInterface
   */
  protected $tracker;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\content_moderation\RevisionTrackerInterface $tracker
   *   The revision tracker.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, RevisionTrackerInterface $tracker) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('content_moderation.revision_tracker')
    );
  }

  /**
   * Determines the default moderation state on load for an entity.
   *
   * This method is only applicable when an entity is loaded that has
   * no moderation state on it, but should. In those cases, failing to set
   * one may result in NULL references elsewhere when other code tries to check
   * the moderation state of the entity.
   *
   * The amount of indirection here makes performance a concern, but
   * given how Entity API works I don't know how else to do it.
   * This reliably gets us *A* valid state. However, that state may be
   * not the ideal one. Suggestions on how to better select the default
   * state here are welcome.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which we want a default state.
   *
   * @return string
   *   The default state for the given entity.
   */
  protected function getDefaultLoadStateId(ContentEntityInterface $entity) {
    return $this->moderationInfo
      ->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle())
      ->getThirdPartySetting('content_moderation', 'default_moderation_state');
  }

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }
    if ($entity->moderation_state->target_id) {
      $moderation_state = $this->entityTypeManager
        ->getStorage('moderation_state')
        ->load($entity->moderation_state->target_id);
      $published_state = $moderation_state->isPublishedState();

      // This entity is default if it is new, the default revision, or the
      // default revision is not published.
      $update_default_revision = $entity->isNew()
        || $moderation_state->isDefaultRevisionState()
        || !$this->isDefaultRevisionPublished($entity);

      // Fire per-entity-type logic for handling the save process.
      $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'moderation')->onPresave($entity, $update_default_revision, $published_state);
    }
  }

  /**
   * Hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_insert()
   */
  public function entityInsert(EntityInterface $entity) {
    if ($this->moderationInfo->isModeratedEntity($entity)) {
      $this->updateOrCreateFromEntity($entity);
      $this->setLatestRevision($entity);
    }
  }

  /**
   * Hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_update()
   */
  public function entityUpdate(EntityInterface $entity) {
    if ($this->moderationInfo->isModeratedEntity($entity)) {
      $this->updateOrCreateFromEntity($entity);
      $this->setLatestRevision($entity);
    }
  }

  /**
   * Creates or updates the moderation state of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update or create a moderation state for.
   */
  protected function updateOrCreateFromEntity(EntityInterface $entity) {
    $moderation_state = $entity->moderation_state->target_id;
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$moderation_state) {
      $moderation_state = $this->moderationInfo
        ->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle())
        ->getThirdPartySetting('content_moderation', 'default_moderation_state');
    }

    // @todo what if $entity->moderation_state->target_id is null at this point?
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $entity_revision_id = $entity->getRevisionId();
    $entity_langcode = $entity->language()->getId();

    $storage = $this->entityTypeManager->getStorage('content_moderation_state');
    $entities = $storage->loadByProperties([
      'content_entity_type_id' => $entity_type_id,
      'content_entity_id' => $entity_id,
    ]);

    /** @var \Drupal\content_moderation\ContentModerationStateInterface $content_moderation_state */
    $content_moderation_state = reset($entities);
    if (!($content_moderation_state instanceof ContentModerationStateInterface)) {
      $content_moderation_state = $storage->create([
        'content_entity_type_id' => $entity_type_id,
        'content_entity_id' => $entity_id,
      ]);
    }
    else {
      // Create a new revision.
      $content_moderation_state->setNewRevision(TRUE);
    }

    // Sync translations.
    if (!$content_moderation_state->hasTranslation($entity_langcode)) {
      $content_moderation_state->addTranslation($entity_langcode);
    }
    if ($content_moderation_state->language()->getId() !== $entity_langcode) {
      $content_moderation_state = $content_moderation_state->getTranslation($entity_langcode);
    }

    // Create the ContentModerationState entity for the inserted entity.
    $content_moderation_state->set('content_entity_revision_id', $entity_revision_id);
    $content_moderation_state->set('moderation_state', $moderation_state);
    ContentModerationState::updateOrCreateFromEntity($content_moderation_state);
  }

  /**
   * Set the latest revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity to create content_moderation_state entity for.
   */
  protected function setLatestRevision(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $this->tracker->setLatestRevision(
      $entity->getEntityTypeId(),
      $entity->id(),
      $entity->language()->getId(),
      $entity->getRevisionId()
    );
  }

  /**
   * Act on entities being assembled before rendering.
   *
   * This is a hook bridge.
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
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if ($entity->isDefaultRevision()) {
      return;
    }

    $component = $display->getComponent('content_moderation_control');
    if ($component) {
      $build['content_moderation_control'] = $this->formBuilder->getForm(EntityModerationForm::class, $entity);
      $build['content_moderation_control']['#weight'] = $component['weight'];
    }
  }

  /**
   * Check if the default revision for the given entity is published.
   *
   * The default revision is the same as the entity retrieved by "default" from
   * the storage handler. If the entity is translated, use the default revision
   * of the same language as the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @return bool
   *   TRUE if the default revision is published. FALSE otherwise.
   */
  protected function isDefaultRevisionPublished(EntityInterface $entity) {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $default_revision = $storage->load($entity->id());

    // Ensure we are comparing the same translation as the current entity.
    if ($default_revision instanceof TranslatableInterface && $default_revision->isTranslatable()) {
      // If there is no translation, then there is no default revision and is
      // therefore not published.
      if (!$default_revision->hasTranslation($entity->language()->getId())) {
        return FALSE;
      }

      $default_revision = $default_revision->getTranslation($entity->language()->getId());
    }

    return $default_revision && $default_revision->moderation_state->entity->isPublishedState();
  }

}
