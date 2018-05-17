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
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\workflows\Entity\Workflow;
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
   * The router builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

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
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, EntityTypeBundleInfoInterface $bundle_info, RouteBuilderInterface $router_builder) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->bundleInfo = $bundle_info;
    $this->routerBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('entity_type.bundle.info'),
      $container->get('router.builder')
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

      // This entity is default if it is new, the default revision, or the
      // default revision is not published.
      $update_default_revision = $entity->isNew()
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
    // When updating workflow settings for Content Moderation, we need to
    // rebuild routes as we may be enabling new entity types and the related
    // entity forms.
    elseif ($entity instanceof Workflow && $entity->getTypePlugin()->getPluginId() == 'content_moderation') {
      $this->routerBuilder->setRebuildNeeded();
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
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('content_moderation_state');

    if (!($content_moderation_state instanceof ContentModerationStateInterface)) {
      $content_moderation_state = $storage->create([
        'content_entity_type_id' => $entity->getEntityTypeId(),
        'content_entity_id' => $entity->id(),
        // Make sure that the moderation state entity has the same language code
        // as the moderated entity.
        'langcode' => $entity->language()->getId(),
      ]);
      $content_moderation_state->workflow->target_id = $workflow->id();
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

    // If a new revision of the content has been created, add a new content
    // moderation state revision.
    if (!$content_moderation_state->isNew() && $content_moderation_state->content_entity_revision_id->value != $entity_revision_id) {
      $content_moderation_state = $storage->createRevision($content_moderation_state, $entity->isDefaultRevision());
    }

    // Create the ContentModerationState entity for the inserted entity.
    $moderation_state = $entity->moderation_state->value;
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$moderation_state) {
      $moderation_state = $workflow->getTypePlugin()->getInitialState($entity)->id();
    }

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
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }
    if (isset($entity->in_preview) && $entity->in_preview) {
      return;
    }
    // If the component is not defined for this display, we have nothing to do.
    if (!$display->getComponent('content_moderation_control')) {
      return;
    }
    // The moderation form should be displayed only when viewing the latest
    // (translation-affecting) revision, unless it was created as published
    // default revision.
    if (!$entity->isLatestRevision() && !$entity->isLatestTranslationAffectedRevision()) {
      return;
    }
    if (($entity->isDefaultRevision() || $entity->wasDefaultRevision()) && ($moderation_state = $entity->get('moderation_state')->value)) {
      $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
      if ($workflow->getTypePlugin()->getState($moderation_state)->isPublishedState()) {
        return;
      }
    }

    $build['content_moderation_control'] = $this->formBuilder->getForm(EntityModerationForm::class, $entity);
  }

}
