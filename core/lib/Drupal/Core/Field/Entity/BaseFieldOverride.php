<?php

namespace Drupal\Core\Field\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldConfigBase;
use Drupal\Core\Field\FieldException;

/**
 * Defines the base field override entity.
 *
 * Allows base fields to be overridden on the bundle level.
 *
 * @ConfigEntityType(
 *   id = "base_field_override",
 *   label = @Translation("Base field override"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Field\BaseFieldOverrideStorage",
 *     "access" = "Drupal\Core\Field\BaseFieldOverrideAccessControlHandler",
 *   },
 *   config_prefix = "base_field_override",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "field_name",
 *     "entity_type",
 *     "bundle",
 *     "label",
 *     "description",
 *     "required",
 *     "translatable",
 *     "default_value",
 *     "default_value_callback",
 *     "settings",
 *     "field_type",
 *   }
 * )
 */
class BaseFieldOverride extends FieldConfigBase {

  /**
   * The base field definition.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  protected $baseFieldDefinition;

  /**
   * The original override.
   */
  public BaseFieldOverride $original;

  /**
   * Creates a base field override object.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $base_field_definition
   *   The base field definition to override.
   * @param string $bundle
   *   The bundle to which the override applies.
   *
   * @return \Drupal\Core\Field\Entity\BaseFieldOverride
   *   A new base field override object.
   */
  public static function createFromBaseFieldDefinition(BaseFieldDefinition $base_field_definition, $bundle) {
    $values = $base_field_definition->toArray();
    $values['bundle'] = $bundle;
    $values['baseFieldDefinition'] = $base_field_definition;
    return \Drupal::entityTypeManager()->getStorage('base_field_override')->create($values);
  }

  /**
   * Constructs a BaseFieldOverride object.
   *
   * In most cases, base field override entities are created via
   * BaseFieldOverride::createFromBaseFieldDefinition($definition, 'bundle')
   *
   * @param array $values
   *   An array of base field bundle override properties, keyed by property
   *   name. The field to override is specified by referring to an existing
   *   field with:
   *   - field_name: The field name.
   *   - entity_type: The entity type.
   *   Additionally, a 'bundle' property is required to indicate the entity
   *   bundle to which the bundle field override is attached to. Other array
   *   elements will be used to set the corresponding properties on the class;
   *   see the class property documentation for details.
   * @param string $entity_type
   *   (optional) The type of the entity to create. Defaults to
   *   'base_field_override'.
   *
   * @throws \Drupal\Core\Field\FieldException
   *   Exception thrown if $values does not contain a field_name, entity_type or
   *   bundle value.
   */
  public function __construct(array $values, $entity_type = 'base_field_override') {
    if (empty($values['field_name'])) {
      throw new FieldException('Attempt to create a base field bundle override of a field without a field_name');
    }
    if (empty($values['entity_type'])) {
      throw new FieldException("Attempt to create a base field bundle override of field {$values['field_name']} without an entity_type");
    }
    if (empty($values['bundle'])) {
      throw new FieldException("Attempt to create a base field bundle override of field {$values['field_name']} without a bundle");
    }

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition() {
    return $this->getBaseFieldDefinition()->getFieldStorageDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayConfigurable($context) {
    return $this->getBaseFieldDefinition()->isDisplayConfigurable($context);
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOptions($display_context) {
    return $this->getBaseFieldDefinition()->getDisplayOptions($display_context);
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return $this->getBaseFieldDefinition()->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return $this->getBaseFieldDefinition()->isComputed();
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->getBaseFieldDefinition()->getClass();
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueIdentifier() {
    return $this->getBaseFieldDefinition()->getUniqueIdentifier();
  }

  /**
   * Gets the base field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   */
  protected function getBaseFieldDefinition() {
    if (!isset($this->baseFieldDefinition)) {
      $fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($this->entity_type);
      $this->baseFieldDefinition = $fields[$this->getName()];
    }
    return $this->baseFieldDefinition;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Field\FieldException
   *   If the bundle is being changed.
   */
  public function preSave(EntityStorageInterface $storage) {
    // Filter out unknown settings and make sure all settings are present, so
    // that a complete field definition is passed to the various hooks and
    // written to config.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $default_settings = $field_type_manager->getDefaultFieldSettings($this->getType());
    $this->settings = array_intersect_key($this->settings, $default_settings) + $default_settings;

    // Call the parent's presave method to perform validate and calculate
    // dependencies.
    parent::preSave($storage);

    if ($this->isNew()) {
      // @todo This assumes that the previous definition isn't some
      //   non-config-based override, but that might not be the case:
      //   https://www.drupal.org/node/2321071.
      $previous_definition = $this->getBaseFieldDefinition();
    }
    else {
      // Some updates are always disallowed.
      if ($this->entity_type != $this->original->entity_type) {
        throw new FieldException("Cannot change the entity_type of an existing base field bundle override (entity type:{$this->entity_type}, bundle:{$this->original->bundle}, field name: {$this->field_name})");
      }
      if ($this->bundle != $this->original->bundle) {
        throw new FieldException("Cannot change the bundle of an existing base field bundle override (entity type:{$this->entity_type}, bundle:{$this->original->bundle}, field name: {$this->field_name})");
      }
      $previous_definition = $this->original;
    }
    // Notify the entity storage.
    $this->entityTypeManager()->getStorage($this->getTargetEntityTypeId())->onFieldDefinitionUpdate($this, $previous_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $field_overrides) {
    $entity_type_manager = \Drupal::entityTypeManager();
    // Clear the cache upfront, to refresh the results of getBundles().
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    /** @var \Drupal\Core\Field\Entity\BaseFieldOverride $field_override */
    foreach ($field_overrides as $field_override) {
      // Inform the system that the field definition is being updated back to
      // its non-overridden state.
      // @todo This assumes that there isn't a non-config-based override that
      //   we're returning to, but that might not be the case:
      //   https://www.drupal.org/node/2321071.
      $entity_type_manager->getStorage($field_override->getTargetEntityTypeId())->onFieldDefinitionUpdate($field_override->getBaseFieldDefinition(), $field_override);
    }
  }

  /**
   * Loads a base field bundle override config entity.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Name of the field.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface|null
   *   The base field bundle override config entity if one exists for the
   *   provided field name, otherwise NULL.
   */
  public static function loadByName($entity_type_id, $bundle, $field_name) {
    return \Drupal::entityTypeManager()->getStorage('base_field_override')->load($entity_type_id . '.' . $bundle . '.' . $field_name);
  }

  /**
   * Implements the magic __sleep() method.
   */
  public function __sleep() {
    // Only serialize necessary properties, excluding those that can be
    // recalculated.
    unset($this->baseFieldDefinition);
    return parent::__sleep();
  }

}
