<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Core\Entity\FieldInstance.
 */

namespace Drupal\field\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\field\FieldException;
use Drupal\field\FieldInstanceInterface;

/**
 * Defines the Field instance entity.
 *
 * @EntityType(
 *   id = "field_instance",
 *   label = @Translation("Field instance"),
 *   module = "field",
 *   controllers = {
 *     "storage" = "Drupal\field\FieldInstanceStorageController"
 *   },
 *   config_prefix = "field.instance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class FieldInstance extends ConfigEntityBase implements FieldInstanceInterface {

  /**
   * The instance ID (machine name).
   *
   * The ID consists of 3 parts: the entity type, bundle and the field name.
   *
   * Example: node.article.body, user.user.field_main_image.
   *
   * @var string
   */
  public $id;

  /**
   * The instance UUID.
   *
   * This is assigned automatically when the instance is created.
   *
   * @var string
   */
  public $uuid;

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
   * Example for a number_integer field:
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
   * - \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being created.
   * - \Drupal\field\Plugin\Core\Entity\Field $field
   *   The field object.
   * - \Drupal\field\Plugin\Core\Entity\FieldInstance $instance
   *   The field instance object.
   * - string $langcode
   *   The language of the entity being created.
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
   * @var \Drupal\field\Plugin\Core\Entity\Field
   */
  protected $field;

  /**
   * Flag indicating whether the bundle name can be renamed or not.
   *
   * @var bool
   */
  protected $bundle_rename_allowed = FALSE;

  /**
   * The original instance.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
   */
  public $original = NULL;

  /**
   * Constructs a FieldInstance object.
   *
   * @param array $values
   *   An array of field instance properties, keyed by property name. Most
   *   array elements will be used to set the corresponding properties on the
   *   class; see the class property documentation for details. Some array
   *   elements have special meanings and a few are required; these special
   *   elements are:
   *   - field_name: optional. The name of the field this is an instance of.
   *   - field_uuid: optional. Either field_uuid or field_name is required
   *     to build field instance. field_name will gain higher priority.
   *     If field_name is not provided, field_uuid will be checked then.
   *   - entity_type: required.
   *   - bundle: required.
   *
   * In most cases, Field instance entities are created via
   * entity_create('field_instance', $values)), where $values is the same
   * parameter as in this constructor.
   *
   * @see entity_create()
   *
   * @ingroup field_crud
   */
  public function __construct(array $values, $entity_type = 'field_instance') {
    // Accept incoming 'field_name' instead of 'field_uuid', for easier DX on
    // creation of new instances.
    if (isset($values['field_name']) && !isset($values['field_uuid'])) {
      $field = field_info_field($values['field_name']);
      if (!$field) {
        throw new FieldException(format_string('Attempt to create an instance of unknown, disabled, or deleted field @field_id', array('@field_id' => $values['field_name'])));
      }
      $values['field_uuid'] = $field->uuid;
    }
    elseif (isset($values['field_uuid'])) {
      $field = field_info_field_by_id($values['field_uuid']);
      // field_info_field_by_id() will not find the field if it is inactive.
      if (!$field) {
        $field = current(field_read_fields(array('uuid' => $values['field_uuid']), array('include_inactive' => TRUE, 'include_deleted' => TRUE)));
      }
      if (!$field) {
        throw new FieldException(format_string('Attempt to create an instance of unknown field @uuid', array('@uuid' => $values['field_uuid'])));
      }
    }
    else {
      throw new FieldException('Attempt to create an instance of an unspecified field.');
    }

    // At this point, we should have a 'field_uuid' and a Field. Ditch the
    // 'field_name' property if it was provided, and assign the $field property.
    unset($values['field_name']);
    $this->field = $field;

    // Discard the 'field_type' entry that is added in config records to ease
    // schema generation. See getExportProperties().
    unset($values['field_type']);

    // Check required properties.
    if (empty($values['entity_type'])) {
      throw new FieldException(format_string('Attempt to create an instance of field @field_id without an entity type.', array('@field_id' => $this->field->id)));
    }
    if (empty($values['bundle'])) {
      throw new FieldException(format_string('Attempt to create an instance of field @field_id without a bundle.', array('@field_id' => $this->field->id)));
    }

    // 'Label' defaults to the field ID (mostly useful for field instances
    // created in tests).
    $values += array(
      'label' => $this->field->id,
    );
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle . '.' . $this->field->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $names = array(
      'id',
      'uuid',
      'status',
      'langcode',
      'field_uuid',
      'entity_type',
      'bundle',
      'label',
      'description',
      'required',
      'default_value',
      'default_value_function',
      'settings',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }

    // Additionally, include the field type, that is needed to be able to
    // generate the field-type-dependant parts of the config schema.
    $properties['field_type'] = $this->field->type;

    return $properties;
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::save().
   *
   * @return
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\field\FieldException
   *   If the field instance definition is invalid.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  public function save() {
    if ($this->isNew()) {
      return $this->saveNew();
    }
    else {
      return $this->saveUpdated();
    }
  }

  /**
   * Saves a new field instance definition.
   *
   * @return
   *   SAVED_NEW if the definition was saved.
   *
   * @throws \Drupal\field\FieldException
   *   If the field instance definition is invalid.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  protected function saveNew() {
    $module_handler = \Drupal::moduleHandler();
    $instance_controller = \Drupal::entityManager()->getStorageController($this->entityType);

    // Check that the field can be attached to this entity type.
    if (!empty($this->field->entity_types) && !in_array($this->entity_type, $this->field->entity_types)) {
      throw new FieldException(format_string('Attempt to create an instance of field @field_id on forbidden entity type @entity_type.', array('@field_id' => $this->field->id, '@entity_type' => $this->entity_type)));
    }

    // Assign the ID.
    $this->id = $this->id();

    // Ensure the field instance is unique within the bundle.
    if ($prior_instance = $instance_controller->load($this->id)) {
      throw new FieldException(format_string('Attempt to create an instance of field @field_id on bundle @bundle that already has an instance of that field.', array('@field_id' => $this->field->id, '@bundle' => $this->bundle)));
    }

    // Set the field UUID.
    $this->field_uuid = $this->field->uuid;

    // Ensure default values are present.
    $this->prepareSave();

    // Save the configuration.
    $result = parent::save();
    field_cache_clear();

    return $result;
  }

  /**
   * Saves an updated field instance definition.
   *
   * @return
   *   SAVED_UPDATED if the definition was saved.
   *
   * @throws \Drupal\field\FieldException
   *   If the field instance definition is invalid.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  protected function saveUpdated() {
    $module_handler = \Drupal::moduleHandler();
    $instance_controller = \Drupal::entityManager()->getStorageController($this->entityType);

    $original = $instance_controller->loadUnchanged($this->getOriginalID());
    $this->original = $original;

    // Some updates are always disallowed.
    if ($this->entity_type != $original->entity_type) {
      throw new FieldException("Cannot change an existing instance's entity_type.");
    }
    if ($this->bundle != $original->bundle && empty($this->bundle_rename_allowed)) {
      throw new FieldException("Cannot change an existing instance's bundle.");
    }
    if ($this->field_uuid != $original->field_uuid) {
      throw new FieldException("Cannot change an existing instance's field.");
    }

    // Ensure default values are present.
    $this->prepareSave();

    // Save the configuration.
    $result = parent::save();
    field_cache_clear();

    return $result;
  }

  /**
   * Prepares the instance definition for saving.
   */
  protected function prepareSave() {
    $field_type_info = field_info_field_types($this->field->type);

    // Set the default instance settings.
    $this->settings += $field_type_info['instance_settings'];
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::delete().
   *
   * @param bool $field_cleanup
   *   (optional) If TRUE, the field will be deleted as well if its last
   *   instance is being deleted. If FALSE, it is the caller's responsibility to
   *   handle the case of fields left without instances. Defaults to TRUE.
   */
  public function delete($field_cleanup = TRUE) {
    if (!$this->deleted) {
      $module_handler = \Drupal::moduleHandler();
      $state = \Drupal::state();

      // Delete the configuration of this instance and save the configuration
      // in the key_value table so we can use it later during
      // field_purge_batch().
      $deleted_instances = $state->get('field.instance.deleted') ?: array();
      $config = $this->getExportProperties();
      $config['deleted'] = TRUE;
      $deleted_instances[$this->uuid] = $config;
      $state->set('field.instance.deleted', $deleted_instances);

      parent::delete();

      // Clear the cache.
      field_cache_clear();

      // Mark instance data for deletion by invoking
      // hook_field_storage_delete_instance().
      $module_handler->invoke($this->field->storage['module'], 'field_storage_delete_instance', array($this));

      // Remove the instance from the entity form displays.
      if ($form_display = entity_load('entity_form_display', $this->entity_type . '.' . $this->bundle . '.default')) {
        $form_display->removeComponent($this->field->id())->save();
      }

      // Remove the instance from the entity displays.
      $ids = array();
      $view_modes = array('default' => array()) + entity_get_view_modes($this->entity_type);
      foreach (array_keys($view_modes) as $view_mode) {
        $ids[] = $this->entity_type . '.' . $this->bundle . '.' . $view_mode;
      }
      foreach (entity_load_multiple('entity_display', $ids) as $display) {
        $display->removeComponent($this->field->id())->save();
      }

      // Delete the field itself if we just deleted its last instance.
      if ($field_cleanup && count($this->field->getBundles()) == 0) {
        $this->field->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getField() {
    return $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->field->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return $this->field->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSettings() {
    return $this->settings + $this->field->getFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSetting($setting_name) {
    if (array_key_exists($setting_name, $this->settings)) {
      return $this->settings[$setting_name];
    }
    else {
      return $this->field->getFieldSetting($setting_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldPropertyNames() {
    $schema = $this->field->getSchema();
    return array_keys($schema['columns']);
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldTranslatable() {
    return $this->field->translatable;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldCardinality() {
    return $this->field->cardinality;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldRequired() {
    return $this->required;
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
  public function offsetExists($offset) {
    return (isset($this->{$offset}) || $offset == 'field_id' || $offset == 'field_name');
  }

  /**
   * {@inheritdoc}
   */
  public function &offsetGet($offset) {
    if ($offset == 'field_id') {
      return $this->field_uuid;
    }
    if ($offset == 'field_name') {
      return $this->field->id;
    }
    return $this->{$offset};
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if ($offset == 'field_id') {
      $offset = 'field_uuid';
    }
    $this->{$offset} = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    if ($offset == 'field_id') {
      $offset = 'field_uuid';
    }
    unset($this->{$offset});
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    // Only store the definition, not external objects or derived data.
    return serialize($this->getExportProperties());
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $this->__construct(unserialize($serialized));
  }

}
