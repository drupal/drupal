<?php

namespace Drupal\content_moderation;

use Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\content_moderation\Entity\Handler\BlockContentModerationHandler;
use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\content_moderation\Entity\Handler\NodeModerationHandler;
use Drupal\content_moderation\Entity\Routing\EntityModerationRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 *
 * @internal
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * A keyed array of custom moderation handlers for given entity types.
   *
   * Any entity not specified will use a common default.
   *
   * @var array
   */
  protected $moderationHandlers = [
    'node' => NodeModerationHandler::class,
    'block_content' => BlockContentModerationHandler::class,
  ];

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service. for form alters.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   Bundle information service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validator
   *   State transition validator.
   */
  public function __construct(TranslationInterface $translation, ModerationInformationInterface $moderation_information, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, AccountInterface $current_user, StateTransitionValidationInterface $validator) {
    $this->stringTranslation = $translation;
    $this->moderationInfo = $moderation_information;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->currentUser = $current_user;
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('current_user'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * Adds Moderation configuration to appropriate entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Internal entity types should never be moderated, and the 'path_alias'
      // entity type needs to be excluded for now.
      // @todo Enable moderation for path aliases after they become publishable
      //   in https://www.drupal.org/project/drupal/issues/3007669.
      // Workspace entities can not be moderated because they use string IDs.
      // @see \Drupal\content_moderation\Entity\ContentModerationState::baseFieldDefinitions()
      // where the target entity ID is defined as an integer.
      // @todo Moderation is disabled for taxonomy terms until integration is
      //   enabled for them.
      // @see https://www.drupal.org/project/drupal/issues/3047110
      $entity_type_to_exclude = [
        'path_alias',
        'workspace',
        'taxonomy_term',
      ];
      if ($entity_type->isRevisionable() && !$entity_type->isInternal() && !in_array($entity_type_id, $entity_type_to_exclude)) {
        $entity_types[$entity_type_id] = $this->addModerationToEntityType($entity_type);
      }
    }
  }

  /**
   * Modifies an entity definition to include moderation support.
   *
   * This primarily just means an extra handler. A Generic one is provided,
   * but individual entity types can provide their own as appropriate.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $type
   *   The content entity definition to modify.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface
   *   The modified content entity definition.
   */
  protected function addModerationToEntityType(ContentEntityTypeInterface $type) {
    if (!$type->hasHandlerClass('moderation')) {
      $handler_class = !empty($this->moderationHandlers[$type->id()]) ? $this->moderationHandlers[$type->id()] : ModerationHandler::class;
      $type->setHandlerClass('moderation', $handler_class);
    }

    if (!$type->hasLinkTemplate('latest-version') && $type->hasLinkTemplate('canonical')) {
      $type->setLinkTemplate('latest-version', $type->getLinkTemplate('canonical') . '/latest');
    }

    $providers = $type->getRouteProviderClasses() ?: [];
    if (empty($providers['moderation'])) {
      $providers['moderation'] = EntityModerationRouteProvider::class;
      $type->setHandlerClass('route_provider', $providers);
    }

    return $type;
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @return array
   *   A nested array of 'pseudo-field' elements. Each list is nested within the
   *   following keys: entity type, bundle name, context (either 'form' or
   *   'display'). The keys are the name of the elements as appearing in the
   *   renderable array (either the entity form or the displayed entity). The
   *   value is an associative array:
   *   - label: The human readable name of the element. Make sure you sanitize
   *     this appropriately.
   *   - description: A short description of the element contents.
   *   - weight: The default weight of the element.
   *   - visible: (optional) The default visibility of the element. Defaults to
   *     TRUE.
   *   - edit: (optional) String containing markup (normally a link) used as the
   *     element's 'edit' operation in the administration interface. Only for
   *     'form' context.
   *   - delete: (optional) String containing markup (normally a link) used as
   *     the element's 'delete' operation in the administration interface. Only
   *     for 'form' context.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $return = [];
    foreach ($this->getModeratedBundles() as $bundle) {
      $return[$bundle['entity']][$bundle['bundle']]['display']['content_moderation_control'] = [
        'label' => $this->t('Moderation control'),
        'description' => $this->t("Status listing and form for the entity's moderation state."),
        'weight' => -20,
        'visible' => TRUE,
      ];
    }

    return $return;
  }

  /**
   * Returns an iterable list of entity names and bundle names under moderation.
   *
   * That is, this method returns a list of bundles that have Content
   * Moderation enabled on them.
   *
   * @return \Generator
   *   A generator, yielding a 2 element associative array:
   *   - entity: The machine name of an entity type, such as "node" or
   *     "block_content".
   *   - bundle: The machine name of a bundle, such as "page" or "article".
   */
  protected function getModeratedBundles() {
    $entity_types = array_filter($this->entityTypeManager->getDefinitions(), [$this->moderationInfo, 'canModerateEntitiesOfEntityType']);
    foreach ($entity_types as $type_name => $type) {
      foreach ($this->bundleInfo->getBundleInfo($type_name) as $bundle_id => $bundle) {
        if ($this->moderationInfo->shouldModerateEntitiesOfBundle($type, $bundle_id)) {
          yield ['entity' => $type_name, 'bundle' => $bundle_id];
        }
      }
    }
  }

  /**
   * Adds base field info to an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type for adding base fields to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   New fields added by moderation state.
   *
   * @see hook_entity_base_field_info()
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    if (!$this->moderationInfo->isModeratedEntityType($entity_type)) {
      return [];
    }

    $fields = [];
    $fields['moderation_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of this piece of content.'))
      ->setComputed(TRUE)
      ->setClass(ModerationStateFieldItemList::class)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'moderation_state_default',
        'weight' => 100,
        'settings' => [],
      ])
      ->addConstraint('ModerationState', [])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setReadOnly(FALSE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Replaces the entity form entity object with a proper revision object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   * @param string $operation
   *   The entity form operation.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see hook_entity_prepare_form()
   */
  public function entityPrepareForm(EntityInterface $entity, $operation, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    if ($this->isModeratedEntityEditForm($form_object) && !$entity->isNew()) {
      // Generate a proper revision object for the current entity. This allows
      // to correctly handle translatable entities having pending revisions.
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      /** @var \Drupal\Core\Entity\ContentEntityInterface $new_revision */
      $new_revision = $storage->createRevision($entity, FALSE);

      // Restore the revision ID as other modules may expect to find it still
      // populated. This will reset the "new revision" flag, however the entity
      // object will be marked as a new revision again on submit.
      // @see \Drupal\Core\Entity\ContentEntityForm::buildEntity()
      $revision_key = $new_revision->getEntityType()->getKey('revision');
      $new_revision->set($revision_key, $new_revision->getLoadedRevisionId());
      $form_object->setEntity($new_revision);
    }
  }

  /**
   * Alters bundle forms to enforce revision handling.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof BundleEntityFormBase) {
      $config_entity = $form_object->getEntity();
      $bundle_of = $config_entity->getEntityType()->getBundleOf();
      if ($bundle_of
          && ($bundle_of_entity_type = $this->entityTypeManager->getDefinition($bundle_of))
          && $this->moderationInfo->shouldModerateEntitiesOfBundle($bundle_of_entity_type, $config_entity->id())) {
        $this->entityTypeManager->getHandler($bundle_of, 'moderation')->enforceRevisionsBundleFormAlter($form, $form_state, $form_id);
      }
    }
    elseif ($this->isModeratedEntityEditForm($form_object)) {
      /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $form_object->getEntity();

      $this->entityTypeManager
        ->getHandler($entity->getEntityTypeId(), 'moderation')
        ->enforceRevisionsEntityFormAlter($form, $form_state, $form_id);

      // Submit handler to redirect to the latest version, if available.
      $form['actions']['submit']['#submit'][] = [EntityTypeInfo::class, 'bundleFormRedirect'];

      // Move the 'moderation_state' field widget to the footer region, if
      // available.
      if (isset($form['footer']) && in_array($form_object->getOperation(), ['edit', 'default'], TRUE)) {
        $form['moderation_state']['#group'] = 'footer';
      }

      // If the publishing status exists in the meta region, replace it with
      // the current state instead.
      if (isset($form['meta']['published'])) {
        $form['meta']['published']['#markup'] = $this->moderationInfo->getWorkflowForEntity($entity)->getTypePlugin()->getState($entity->moderation_state->value)->label();
      }
    }
  }

  /**
   * Checks whether the specified form allows to edit a moderated entity.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The form object.
   *
   * @return bool
   *   TRUE if the form should get form moderation, FALSE otherwise.
   */
  protected function isModeratedEntityEditForm(FormInterface $form_object) {
    return $form_object instanceof ContentEntityFormInterface &&
      in_array($form_object->getOperation(), ['edit', 'default', 'layout_builder'], TRUE) &&
      $this->moderationInfo->isModeratedEntity($form_object->getEntity());
  }

  /**
   * Redirect content entity edit forms on save, if there is a pending revision.
   *
   * When saving their changes, editors should see those changes displayed on
   * the next page.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function bundleFormRedirect(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $moderation_info = \Drupal::getContainer()->get('content_moderation.moderation_information');
    if ($moderation_info->hasPendingRevision($entity) && $entity->hasLinkTemplate('latest-version')) {
      $entity_type_id = $entity->getEntityTypeId();
      $form_state->setRedirect("entity.$entity_type_id.latest_version", [$entity_type_id => $entity->id()]);
    }
  }

}
