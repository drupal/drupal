<?php

namespace Drupal\content_moderation;

use Drupal\content_moderation\Entity\ContentModerationState as ContentModerationStateEntity;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\content_moderation\Form\EntityModerationForm;
use Drupal\workflows\WorkflowInterface;
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
   * @param \Drupal\content_moderation\RevisionTrackerInterface $tracker
   *   The revision tracker.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity bundle information service.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, RevisionTrackerInterface $tracker, EntityTypeBundleInfoInterface $bundle_info) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->tracker = $tracker;
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
      $container->get('content_moderation.revision_tracker'),
      $container->get('entity_type.bundle.info')
    );
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

    if ($entity->moderation_state->value) {
      $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
      /** @var \Drupal\content_moderation\ContentModerationState $current_state */
      $current_state = $workflow->getState($entity->moderation_state->value);

      // This entity is default if it is new, a new translation, the default
      // revision, or the default revision is not published.
      $update_default_revision = $entity->isNew()
        || $entity->isNewTranslation()
        || $current_state->isDefaultRevisionState()
        || !$this->isDefaultRevisionPublished($entity, $workflow);

      // Fire per-entity-type logic for handling the save process.
      $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'moderation')->onPresave($entity, $update_default_revision, $current_state->isPublishedState());
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
    $moderation_state = $entity->moderation_state->value;
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$moderation_state) {
      $moderation_state = $workflow->getTypePlugin()->getInitialState($workflow, $entity)->id();
    }

    // @todo what if $entity->moderation_state is null at this point?
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $entity_revision_id = $entity->getRevisionId();

    $storage = $this->entityTypeManager->getStorage('content_moderation_state');
    $entities = $storage->loadByProperties([
      'content_entity_type_id' => $entity_type_id,
      'content_entity_id' => $entity_id,
      'workflow' => $workflow->id(),
    ]);

    /** @var \Drupal\content_moderation\ContentModerationStateInterface $content_moderation_state */
    $content_moderation_state = reset($entities);
    if (!($content_moderation_state instanceof ContentModerationStateInterface)) {
      $content_moderation_state = $storage->create([
        'content_entity_type_id' => $entity_type_id,
        'content_entity_id' => $entity_id,
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
    $content_moderation_state->set('content_entity_revision_id', $entity_revision_id);
    $content_moderation_state->set('moderation_state', $moderation_state);
    ContentModerationStateEntity::updateOrCreateFromEntity($content_moderation_state);
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
    if ($this->moderationInfo->isLiveRevision($entity)) {
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
   * the storage handler. If the entity is translated, check if any of the
   * translations are published.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow being applied to the entity.
   *
   * @return bool
   *   TRUE if the default revision is published. FALSE otherwise.
   */
  protected function isDefaultRevisionPublished(EntityInterface $entity, WorkflowInterface $workflow) {
    $default_revision = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->load($entity->id());

    // Ensure we are checking all translations of the default revision.
    if ($default_revision instanceof TranslatableInterface && $default_revision->isTranslatable()) {
      // Loop through each language that has a translation.
      foreach ($default_revision->getTranslationLanguages() as $language) {
        // Load the translated revision.
        $language_revision = $default_revision->getTranslation($language->getId());
        // Return TRUE if a translation with a published state is found.
        if ($workflow->getState($language_revision->moderation_state->value)->isPublishedState()) {
          return TRUE;
        }
      }
    }

    return $workflow->getState($default_revision->moderation_state->value)->isPublishedState();
  }

}
