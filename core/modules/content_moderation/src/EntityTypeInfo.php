<?php

namespace Drupal\content_moderation;

use Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\content_moderation\Entity\Handler\BlockContentModerationHandler;
use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\content_moderation\Entity\Handler\NodeModerationHandler;
use Drupal\content_moderation\Form\BundleModerationConfigurationForm;
use Drupal\content_moderation\Routing\EntityModerationRouteProvider;
use Drupal\content_moderation\Routing\EntityTypeModerationRouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   */
  public function __construct(TranslationInterface $translation, ModerationInformationInterface $moderation_information, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->stringTranslation = $translation;
    $this->moderationInfo = $moderation_information;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }


  /**
   * Adds Moderation configuration to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($this->filterNonRevisionableEntityTypes($entity_types) as $type_name => $type) {
      $entity_types[$type_name] = $this->addModerationToEntityType($type);
      $entity_types[$type->get('bundle_of')] = $this->addModerationToEntity($entity_types[$type->get('bundle_of')]);
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
  protected function addModerationToEntity(ContentEntityTypeInterface $type) {
    if (!$type->hasHandlerClass('moderation')) {
      $handler_class = !empty($this->moderationHandlers[$type->id()]) ? $this->moderationHandlers[$type->id()] : ModerationHandler::class;
      $type->setHandlerClass('moderation', $handler_class);
    }

    if (!$type->hasLinkTemplate('latest-version') && $type->hasLinkTemplate('canonical')) {
      $type->setLinkTemplate('latest-version', $type->getLinkTemplate('canonical') . '/latest');
    }

    // @todo Core forgot to add a direct way to manipulate route_provider, so
    // we have to do it the sloppy way for now.
    $providers = $type->getRouteProviderClasses() ?: [];
    if (empty($providers['moderation'])) {
      $providers['moderation'] = EntityModerationRouteProvider::class;
      $type->setHandlerClass('route_provider', $providers);
    }

    return $type;
  }

  /**
   * Configures moderation configuration support on a entity type definition.
   *
   * That "configuration support" includes a configuration form, a hypermedia
   * link, and a route provider to tie it all together. There's also a
   * moderation handler for per-entity-type variation.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $type
   *   The config entity definition to modify.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   *   The modified config entity definition.
   */
  protected function addModerationToEntityType(ConfigEntityTypeInterface $type) {
    if ($type->hasLinkTemplate('edit-form') && !$type->hasLinkTemplate('moderation-form')) {
      $type->setLinkTemplate('moderation-form', $type->getLinkTemplate('edit-form') . '/moderation');
    }

    if (!$type->getFormClass('moderation')) {
      $type->setFormClass('moderation', BundleModerationConfigurationForm::class);
    }

    // @todo Core forgot to add a direct way to manipulate route_provider, so
    // we have to do it the sloppy way for now.
    $providers = $type->getRouteProviderClasses() ?: [];
    if (empty($providers['moderation'])) {
      $providers['moderation'] = EntityTypeModerationRouteProvider::class;
      $type->setHandlerClass('route_provider', $providers);
    }

    return $type;
  }

  /**
   * Adds an operation on bundles that should have a Moderation form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    $type = $entity->getEntityType();
    $bundle_of = $type->getBundleOf();
    if ($this->currentUser->hasPermission('administer moderation states') && $bundle_of &&
      $this->moderationInfo->canModerateEntitiesOfEntityType($this->entityTypeManager->getDefinition($bundle_of))
    ) {
      $operations['manage-moderation'] = [
        'title' => t('Manage moderation'),
        'weight' => 27,
        'url' => Url::fromRoute("entity.{$type->id()}.moderation", [$entity->getEntityTypeId() => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * This is a hook bridge.
   *
   * @see hook_entity_extra_field_info()
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
    /** @var ConfigEntityTypeInterface $type */
    foreach ($this->filterNonRevisionableEntityTypes($this->entityTypeManager->getDefinitions()) as $type_name => $type) {
      $result = $this->entityTypeManager
        ->getStorage($type_name)
        ->getQuery()
        ->condition('third_party_settings.content_moderation.enabled', TRUE)
        ->execute();

      foreach ($result as $bundle_name) {
        yield ['entity' => $type->getBundleOf(), 'bundle' => $bundle_name];
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
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    if (!$this->moderationInfo->canModerateEntitiesOfEntityType($entity_type)) {
      return [];
    }

    $fields = [];
    $fields['moderation_state'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of this piece of content.'))
      ->setComputed(TRUE)
      ->setClass(ModerationStateFieldItemList::class)
      ->setSetting('target_type', 'moderation_state')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'moderation_state_default',
        'weight' => 5,
        'settings' => [],
      ])
      ->addConstraint('ModerationState', [])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Adds ModerationState constraint to bundles whose entities are moderated.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
   *   The array of bundle field definitions.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $bundle
   *   The bundle.
   *
   * @see hook_entity_bundle_field_info_alter();
   */
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle) {
    if (!empty($fields['moderation_state']) && $this->moderationInfo->shouldModerateEntitiesOfBundle($entity_type, $bundle)) {
      $fields['moderation_state']->addConstraint('ModerationState', []);
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
      $type = $form_object->getEntity()->getEntityType();
      if ($this->moderationInfo->canModerateEntitiesOfEntityType($type)) {
        $this->entityTypeManager->getHandler($type->getBundleOf(), 'moderation')->enforceRevisionsBundleFormAlter($form, $form_state, $form_id);
      }
    }
    elseif ($form_object instanceof ContentEntityFormInterface) {
      $entity = $form_object->getEntity();
      if ($this->moderationInfo->isModeratedEntity($entity)) {
        $this->entityTypeManager
          ->getHandler($entity->getEntityTypeId(), 'moderation')
          ->enforceRevisionsEntityFormAlter($form, $form_state, $form_id);
        // Submit handler to redirect to the latest version, if available.
        $form['actions']['submit']['#submit'][] = [EntityTypeInfo::class, 'bundleFormRedirect'];
      }
    }
  }

  /**
   * Redirect content entity edit forms on save, if there is a forward revision.
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
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $moderation_info = \Drupal::getContainer()->get('content_moderation.moderation_information');
    if ($moderation_info->hasForwardRevision($entity) && $entity->hasLinkTemplate('latest-version')) {
      $entity_type_id = $entity->getEntityTypeId();
      $form_state->setRedirect("entity.$entity_type_id.latest_version", [$entity_type_id => $entity->id()]);
    }
  }

  /**
   * Filters entity type lists to return only revisionable entity types.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list filter.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface[]
   *   An array of revisionable entity types which are configuration entities.
   */
  protected function filterNonRevisionableEntityTypes(array $entity_types) {
    return array_filter($entity_types, function (EntityTypeInterface $type) use ($entity_types) {
      return ($type instanceof ConfigEntityTypeInterface)
      && ($bundle_of = $type->get('bundle_of'))
      && $entity_types[$bundle_of]->isRevisionable();
    });
  }

}
