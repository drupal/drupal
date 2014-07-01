<?php

/**
 * @file
 * Contains \Drupal\field\Entity\FieldInstanceConfig.
 */

namespace Drupal\field\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\field\FieldException;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldInstanceConfigInterface;

/**
 * Defines the Field instance entity.
 *
 * @ConfigEntityType(
 *   id = "field_instance_config",
 *   label = @Translation("Field instance"),
 *   controllers = {
 *     "access" = "Drupal\field\FieldInstanceConfigAccessController",
 *     "storage" = "Drupal\field\FieldInstanceConfigStorage"
 *   },
 *   config_prefix = "instance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class FieldInstanceConfig extends ConfigEntityBase implements FieldInstanceConfigInterface {

  /**
   * The instance ID.
   *
   * The ID consists of 3 parts: the entity type, bundle and the field name.
   *
   * Example: node.article.body, user.user.field_main_image.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the field attached to the bundle by this instance.
   *
   * @var string
   */
  public $field_name;

  /**
   * The UUID of the field attached to the bundle by this instance.
   *
   * @var string
   */
  public $field_uuid;

  /**
   * The name of the entity type the instance is attached to.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The name of the bundle the instance is attached to.
   *
   * @var string
   */
  public $bundle;

  /**
   * The human-readable label for the instance.
   *
   * This will be used as the title of Form API elements for the field in entity
   * edit forms, or as the label for the field values in displayed entities.
   *
   * If not specified, this defaults to the field_name (mostly useful for field
   * instances created in tests).
   *
   * @var string
   */
  public $label;

  /**
   * The instance description.
   *
   * A human-readable description for the field when used with this bundle.
   * For example, the description will be the help text of Form API elements for
   * this instance in entity edit forms.
   *
   * @var string
   */
  public $description = '';

  /**
   * Field-type specific settings.
   *
   * An array of key/value pairs. The keys and default values are defined by the
   * field type.
   *
   * @var array
   */
  public $settings = array();

  /**
   * Flag indicating whether the field is required.
   *
   * TRUE if a value for this field is required when used with this bundle,
   * FALSE otherwise. Currently, required-ness is only enforced at the Form API
   * level in entity edit forms, not during direct API saves.
   *
   * @var bool
   */
  public $required = FALSE;

  /**
   * Flag indicating whether the field is translatable.
   *
   * Defaults to TRUE.
   *
   * @var bool
   */
  public $translatable = TRUE;

  /**
   * Default field value.
   *
   * The default value is used when an entity is created, either:
   * - through an entity creation form; the form elements for the field are
   *   prepopulated with the default value.
   * - through direct API calls (i.e. $entity->save()); the default value is
   *   added if the $entity object provides no explicit entry (actual values or
   *   "the field is empty") for the field.
   *
   * The default value is expressed as a numerically indexed array of items,
   * each item being an array of key/value pairs matching the set of 'columns'
   * defined by the "field schema" for the field type, as exposed in
   * hook_field_schema(). If the number of items exceeds the cardinality of the
   * field, extraneous items will be ignored.
   *
   * This property is overlooked if the $default_value_function is non-empty.
   *
   * Example for a integer field:
   * @code
   * array(
   *   array('value' => 1),
   *   array('value' => 2),
   * )
   * @endcode
   *
   * @var array
   */
  public $default_value = array();

  /**
   * The name of a callback function that returns default values.
   *
   * The function will be called with the following arguments:
   * - \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being created.
   * - \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   * It should return an array of default values, in the same format as the
   * $default_value property.
   *
   * This property takes precedence on the list of fixed values specified in the
   * $default_value property.
   *
   * @var string
   */
  public $default_value_function = '';

  /**
   * Flag indicating whether the instance is deleted.
   *
   * The delete() method marks the instance as "deleted" and removes the
   * corresponding entry from the config storage, but keeps its definition in
   * the state storage while field data is purged by a separate
   * garbage-collection process.
   *
   * Deleted instances stay out of the regular entity lifecycle (notably, their
   * values are not populated in loaded entities, and are not saved back).
   *
   * @var bool
   */
  public $deleted = FALSE;

  /**
   * The field ConfigEntity object corresponding to $field_uuid.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Flag indicating whether the bundle name can be renamed or not.
   *
   * @var bool
   */
  protected $bundle_rename_allowed = FALSE;

  /**
   * The data definition of a field item.
   *
   * @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition
   */
  protected $itemDefinition;

  /**
   * Constructs a FieldInstanceConfig object.
   *
   * In most cases, Field instance entities are created via
   * entity_create('field_instance_config', $values), where $values is the same
   * parameter as in this constructor.
   *
   * @param array $values
   *   An array of field instance properties, keyed by property name. The field
   *   this is an instance of can be specified either with:
   *   - field: the FieldConfigInterface object,
   *   or by referring to an existing field in the current configuration with:
   *   - field_name: The field name.
   *   - entity_type: The entity type.
   *   Additionally, a 'bundle' property is required to indicate the entity
   *   bundle to which the instance is attached to. Other array elements will be
   *   used to set the corresponding properties on the class; see the class
   *   property documentation for details.
   *
   * @see entity_create()
   */
  public function __construct(array $values, $entity_type = 'field_instance_config') {
    // Allow either an injected FieldConfig object, or a field_name and
    // entity_type.
    if (isset($values['field'])) {
      if (!$values['field'] instanceof FieldConfigInterface) {
        throw new FieldException('Attempt to create a configurable instance of a non-configurable field.');
      }
      $field = $values['field'];
      $values['field_name'] = $field->getName();
      $values['entity_type'] = $field->getTargetEntityTypeId();
      $this->field = $field;
    }
    else {
      if (empty($values['field_name'])) {
        throw new FieldException('Attempt to create an instance of a field without a field_name.');
      }
      if (empty($values['entity_type'])) {
        throw new FieldException(String::format('Attempt to create an instance of field @field_name without an entity_type.', array('@field_name' => $values['field_name'])));
      }
    }
    // 'bundle' is required in either case.
    if (empty($values['bundle'])) {
      throw new FieldException(String::format('Attempt to create an instance of field @field_name without a bundle.', array('@field_name' => $values['field_name'])));
    }

    // Discard the 'field_type' entry that is added in config records to ease
    // schema generation. See self::toArray().
    unset($values['field_type']);

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle . '.' . $this->field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getFieldStorageDefinition()->getType();
  }


  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    // Additionally, include the field type, that is needed to be able to
    // generate the field-type-dependant parts of the config schema.
    $properties['field_type'] = $this->getType();

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    // Validate that we have a valid field for this instance. This throws an
    // exception if the field is invalid.
    $field = $this->getFieldStorageDefinition();

    // Make sure the field_uuid is populated.
    $this->field_uuid = $field->uuid();

    // 'Label' defaults to the field name (mostly useful for field instances
    // created in tests).
    if (empty($this->label)) {
      $this->label = $this->getName();
    }
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::preSave().
   *
   * @throws \Drupal\field\FieldException
   *   If the field instance definition is invalid.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  public function preSave(EntityStorageInterface $storage) {
    $entity_manager = \Drupal::entityManager();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    $field = $this->getFieldStorageDefinition();

    if ($this->isNew()) {
      // Set the default instance settings.
      $this->settings += $field_type_manager->getDefaultInstanceSettings($field->type);
      // Notify the entity storage.
      $entity_manager->getStorage($this->entity_type)->onFieldDefinitionCreate($this);
    }
    else {
      // Some updates are always disallowed.
      if ($this->entity_type != $this->original->entity_type) {
        throw new FieldException("Cannot change an existing instance's entity_type.");
      }
      if ($this->bundle != $this->original->bundle && empty($this->bundle_rename_allowed)) {
        throw new FieldException("Cannot change an existing instance's bundle.");
      }
      if ($this->field_uuid != $this->original->field_uuid) {
        throw new FieldException("Cannot change an existing instance's field.");
      }
      // Set the default instance settings.
      $this->settings += $field_type_manager->getDefaultInstanceSettings($field->type);
      // Notify the entity storage.
      $entity_manager->getStorage($this->entity_type)->onFieldDefinitionUpdate($this, $this->original);
    }
    if (!$this->isSyncing()) {
      // Ensure the correct dependencies are present.
      $this->calculateDependencies();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Manage dependencies.
    $this->addDependency('entity', $this->getFieldStorageDefinition()->getConfigDependencyName());
    $bundle_entity_type_id = \Drupal::entityManager()->getDefinition($this->entity_type)->getBundleEntityType();
    if ($bundle_entity_type_id != 'bundle') {
      // If the target entity type uses entities to manage its bundles then
      // depend on the bundle entity.
      $bundle_entity = \Drupal::entityManager()->getStorage($bundle_entity_type_id)->load($this->bundle);
      $this->addDependency('entity', $bundle_entity->getConfigDependencyName());
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Clear the cache.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Invalidate the render cache for all affected entities.
    $entity_manager = \Drupal::entityManager();
    $entity_type = $this->getFieldStorageDefinition()->getTargetEntityTypeId();
    if ($entity_manager->hasController($entity_type, 'view_builder')) {
      $entity_manager->getViewBuilder($entity_type)->resetCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $instances) {
    $state = \Drupal::state();

    // Keep the instance definitions in the state storage so we can use them
    // later during field_purge_batch().
    $deleted_instances = $state->get('field.instance.deleted') ?: array();
    foreach ($instances as $instance) {
      if (!$instance->deleted) {
        $config = $instance->toArray();
        $config['deleted'] = TRUE;
        $deleted_instances[$instance->uuid()] = $config;
      }
    }
    $state->set('field.instance.deleted', $deleted_instances);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $instances) {
    $field_storage = \Drupal::entityManager()->getStorage('field_config');

    // Clear the cache upfront, to refresh the results of getBundles().
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Notify the entity storage.
    foreach ($instances as $instance) {
      if (!$instance->deleted) {
        \Drupal::entityManager()->getStorage($instance->entity_type)->onFieldDefinitionDelete($instance);
      }
    }

    // If this is part of a configuration synchronization then the following
    // configuration updates are not necessary.
    $entity = reset($instances);
    if ($entity->isSyncing()) {
      return;
    }

    // Delete fields that have no more instances.
    $fields_to_delete = array();
    foreach ($instances as $instance) {
      $field = $instance->getFieldStorageDefinition();
      if (!$instance->deleted && empty($instance->noFieldDelete) && !$instance->isUninstalling() && count($field->getBundles()) == 0) {
        // Key by field UUID to avoid deleting the same field twice.
        $fields_to_delete[$instance->field_uuid] = $field;
      }
    }
    if ($fields_to_delete) {
      $field_storage->delete($fields_to_delete);
    }

    // Cleanup entity displays.
    $displays_to_update = array();
    foreach ($instances as $instance) {
      if (!$instance->deleted) {
        $view_modes = \Drupal::entityManager()->getViewModeOptions($instance->entity_type, TRUE);
        foreach (array_keys($view_modes) as $mode) {
          $displays_to_update['entity_view_display'][$instance->entity_type . '.' . $instance->bundle . '.' . $mode][] = $instance->field->name;
        }
        $form_modes = \Drupal::entityManager()->getFormModeOptions($instance->entity_type, TRUE);
        foreach (array_keys($form_modes) as $mode) {
          $displays_to_update['entity_form_display'][$instance->entity_type . '.' . $instance->bundle . '.' . $mode][] = $instance->field->name;
        }
      }
    }
    foreach ($displays_to_update as $type => $ids) {
      foreach (entity_load_multiple($type, array_keys($ids)) as $id => $display) {
        foreach ($ids[$id] as $field_name) {
          $display->removeComponent($field_name);
        }
        $display->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition() {
    if (!$this->field) {
      $fields = \Drupal::entityManager()->getFieldStorageDefinitions($this->entity_type);
      if (!isset($fields[$this->field_name])) {
        throw new FieldException(String::format('Attempt to create an instance of field @field_name that does not exist on entity type @entity_type.', array('@field_name' => $this->field_name, '@entity_type' => $this->entity_type)));
      }
      if (!$fields[$this->field_name] instanceof FieldConfigInterface) {
        throw new FieldException(String::format('Attempt to create a configurable instance of non-configurable field @field_name.', array('@field_name' => $this->field_name, '@entity_type' => $this->entity_type)));
      }
      $this->field = $fields[$this->field_name];
    }

    return $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings + $this->getFieldStorageDefinition()->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    if (array_key_exists($setting_name, $this->settings)) {
      return $this->settings[$setting_name];
    }
    else {
      return $this->getFieldStorageDefinition()->getSetting($setting_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // A field can be enabled for translation only if translation is supported.
    return $this->translatable && $this->getFieldStorageDefinition()->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslatable($translatable) {
    $this->translatable = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function linkTemplates() {
    $link_templates = parent::linkTemplates();
    if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
      $link_templates['edit-form'] = 'field_ui.instance_edit_' . $this->entity_type;
      $link_templates['field-settings-form'] = 'field_ui.field_edit_' . $this->entity_type;
      $link_templates['delete-form'] = 'field_ui.delete_' . $this->entity_type;

      if (isset($link_templates['drupal:config-translation-overview'])) {
        $link_templates['drupal:config-translation-overview'] .= $link_templates['edit-form'];
      }
    }
    return $link_templates;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = parent::urlRouteParameters($rel);
    $entity_type = \Drupal::entityManager()->getDefinition($this->entity_type);
    $parameters[$entity_type->getBundleEntityType()] = $this->bundle;
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue(ContentEntityInterface $entity) {
    // Allow custom default values function.
    if ($function = $this->default_value_function) {
      $value = call_user_func($function, $entity, $this);
    }
    else {
      $value = $this->default_value;
    }
    // Allow the field type to process default values.
    $field_item_list_class = $this->getClass();
    return $field_item_list_class::processDefaultValue($value, $entity, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayConfigurable($context) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOptions($display_context) {
    // Hide configurable fields by default.
    return array('type' => 'hidden');
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function allowBundleRename() {
    $this->bundle_rename_allowed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function targetBundle() {
    return $this->bundle;
  }

  /*
   * Implements the magic __sleep() method.
   *
   * Using the Serialize interface and serialize() / unserialize() methods
   * breaks entity forms in PHP 5.4.
   * @todo Investigate in https://drupal.org/node/2074253.
   */
  public function __sleep() {
    // Only serialize properties from self::toArray().
    $properties = array_keys(array_intersect_key($this->toArray(), get_object_vars($this)));
    // Serialize $entityTypeId property so that toArray() works when waking up.
    $properties[] = 'entityTypeId';
    return $properties;
  }

  /**
   * Implements the magic __wakeup() method.
   */
  public function __wakeup() {
    // Run the values from self::toArray() through __construct().
    $values = array_intersect_key($this->toArray(), get_object_vars($this));
    $this->__construct($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($type) {
    // Forward to the field definition class for creating new data definitions
    // via the typed manager.
    return FieldDefinition::createFromDataType($type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromItemType($item_type) {
    // Forward to the field definition class for creating new data definitions
    // via the typed manager.
    return FieldDefinition::createFromItemType($item_type);
  }

  public function getDataType() {
    return 'list';
  }

  /**
   * {@inheritdoc}
   */
  public function isList() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    // Derive list class from the field type.
    $type_definition = \Drupal::service('plugin.manager.field.field_type')
      ->getDefinition($this->getType());
    return $type_definition['list_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return \Drupal::typedDataManager()->getDefaultConstraints($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraint($constraint_name) {
    $constraints = $this->getConstraints();
    return isset($constraints[$constraint_name]) ? $constraints[$constraint_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefinition() {
    if (!isset($this->itemDefinition)) {
      $this->itemDefinition = FieldItemDataDefinition::create($this)
        ->setSettings($this->getSettings());
    }
    return $this->itemDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    return $this->deleted;
  }

  /**
   * Loads a field config entity based on the entity type and field name.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Name of the field.
   *
   * @return static
   *   The field instance config entity if one exists for the provided field
   *   name, otherwise NULL.
   */
  public static function loadByName($entity_type_id, $bundle, $field_name) {
    return \Drupal::entityManager()->getStorage('field_instance_config')->load($entity_type_id . '.' . $bundle . '.' . $field_name);
  }

}
