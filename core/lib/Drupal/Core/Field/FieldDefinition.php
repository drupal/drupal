<?php

namespace Drupal\Core\Field;

use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * A class for defining entity field definitions.
 *
 * A field definition in the context of a bundle field is different from a base
 * field in that it may exist only for one or more bundles of an entity type. A
 * bundle field definition may also override the definition of an existing base
 * field definition on a per bundle basis. The bundle field definition is used
 * for code driven overrides, while the
 * \Drupal\Core\Field\Entity\BaseFieldOverride uses config to override the base
 * field definition.
 *
 * Bundle fields can be defined in code using hook_entity_bundle_field_info() or
 * via the
 * \Drupal\Core\Entity\FieldableEntityInterface::bundleFieldDefinitions() method
 * when defining an entity type. All bundle fields require an associated storage
 * definition. A storage definition may have automatically been defined when
 * overriding a base field or it may be manually provided via
 * hook_entity_field_storage_info().
 *
 * @see \Drupal\Core\Entity\FieldableEntityInterface::bundleFieldDefinitions()
 * @see \Drupal\Core\Field\FieldDefinitionInterface
 * @see \Drupal\Core\Field\FieldStorageDefinitionInterface
 * @see hook_entity_bundle_field_info()
 * @see hook_entity_field_storage_info()
 */
class FieldDefinition extends ListDataDefinition implements FieldDefinitionInterface {

  use UnchangingCacheableDependencyTrait;
  use FieldInputValueNormalizerTrait;

  /**
   * The associated field storage definition.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorageDefinition;

  /**
   * Creates a new field definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storageDefinition
   *   The associated field storage definition.
   *
   * @return static
   */
  public static function createFromFieldStorageDefinition(FieldStorageDefinitionInterface $storageDefinition) {
    $field_definition = new static();
    $field_definition->setFieldStorageDefinition($storageDefinition);
    return $field_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->getFieldStorageDefinition()->getName();
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
  public function getTargetEntityTypeId() {
    return $this->getFieldStorageDefinition()->getTargetEntityTypeId();
  }

  /**
   * Set the target bundle.
   *
   * @param string $bundle
   *   The target bundle.
   *
   * @return $this
   */
  public function setTargetBundle($bundle) {
    $this->definition['bundle'] = $bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->definition['bundle'];
  }

  /**
   * Sets whether the display for the field can be configured.
   *
   * @param string $display_context
   *   The display context. Either 'view' or 'form'.
   * @param bool $configurable
   *   Whether the display options can be configured (e.g., via the "Manage
   *   display" / "Manage form display" UI screens). If TRUE, the options
   *   specified via getDisplayOptions() act as defaults.
   *
   * @return $this
   */
  public function setDisplayConfigurable($display_context, $configurable) {
    // If no explicit display options have been specified, default to 'hidden'.
    if (empty($this->definition['display'][$display_context])) {
      $this->definition['display'][$display_context]['options'] = ['region' => 'hidden'];
    }
    $this->definition['display'][$display_context]['configurable'] = $configurable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayConfigurable($display_context) {
    return $this->definition['display'][$display_context]['configurable'] ?? FALSE;
  }

  /**
   * Sets the display options for the field in forms or rendered entities.
   *
   * This enables generic rendering of the field with widgets / formatters,
   * including automated support for "In place editing", and with optional
   * configurability in the "Manage display" / "Manage form display" UI screens.
   *
   * Unless this method is called, the field remains invisible (or requires
   * ad-hoc rendering logic).
   *
   * @param string $display_context
   *   The display context. Either 'view' or 'form'.
   * @param array $options
   *   An array of display options. Refer to
   *   \Drupal\Core\Field\FieldDefinitionInterface::getDisplayOptions() for
   *   a list of supported keys. The options should include at least a 'weight',
   *   or specify 'region' = 'hidden'. The 'default_widget'/'default_formatter'
   *   for the field type will be used if no 'type' is specified.
   *
   * @return $this
   */
  public function setDisplayOptions($display_context, array $options) {
    $this->definition['display'][$display_context]['options'] = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOptions($display_context) {
    return $this->definition['display'][$display_context]['options'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValueLiteral() {
    return $this->definition['default_value'] ?? [];
  }

  /**
   * Set the default value callback for the field.
   *
   * @param string $callback
   *   The default value callback.
   *
   * @return $this
   */
  public function setDefaultValueCallback($callback) {
    if (isset($callback) && !is_string($callback)) {
      throw new \InvalidArgumentException('Default value callback must be a string, like "function_name" or "ClassName::methodName"');
    }
    $this->definition['default_value_callback'] = $callback;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValueCallback() {
    return $this->definition['default_value_callback'] ?? NULL;
  }

  /**
   * Set a default value for the field.
   *
   * @param mixed $value
   *   The default value.
   *
   * @return $this
   */
  public function setDefaultValue($value) {
    $this->definition['default_value'] = $this->normalizeValue($value, $this->getFieldStorageDefinition()->getMainPropertyName());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue(FieldableEntityInterface $entity) {
    // Allow custom default values function.
    if ($callback = $this->getDefaultValueCallback()) {
      $value = call_user_func($callback, $entity, $this);
    }
    else {
      $value = $this->getDefaultValueLiteral();
    }
    $value = $this->normalizeValue($value, $this->getFieldStorageDefinition()->getMainPropertyName());
    // Allow the field type to process default values.
    $field_item_list_class = $this->getClass();
    return $field_item_list_class::processDefaultValue($value, $entity, $this);
  }

  /**
   * Sets whether the field is translatable.
   *
   * @param bool $translatable
   *   Whether the field is translatable.
   *
   * @return $this
   */
  public function setTranslatable($translatable) {
    $this->definition['translatable'] = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return !empty($this->definition['translatable']) && $this->getFieldStorageDefinition()->isTranslatable();
  }

  /**
   * Set the field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storageDefinition
   *   The field storage definition associated with this field definition.
   *
   * @return $this
   */
  public function setFieldStorageDefinition(FieldStorageDefinitionInterface $storageDefinition) {
    $this->fieldStorageDefinition = $storageDefinition;
    $this->itemDefinition = FieldItemDataDefinition::create($this);
    // Create a definition for the items, and initialize it with the default
    // settings for the field type.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $default_settings = $field_type_manager->getDefaultFieldSettings($storageDefinition->getType());
    $this->itemDefinition->setSettings($default_settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition() {
    return $this->fieldStorageDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($bundle) {
    // @todo provide a FieldDefinitionOverride config entity in
    // https://www.drupal.org/project/drupal/issues/2935978.
    throw new \Exception('Field definitions do not currently have an override config entity.');
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueIdentifier() {
    return $this->getTargetEntityTypeId() . '-' . $this->getTargetBundle() . '-' . $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    if (array_key_exists($setting_name, $this->itemDefinition->getSettings())) {
      return $this->itemDefinition->getSetting($setting_name);
    }
    else {
      return $this->getFieldStorageDefinition()->getSetting($setting_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->getItemDefinition()->getSettings() + $this->getFieldStorageDefinition()->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($setting_name, $value) {
    $this->getItemDefinition()->setSetting($setting_name, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    // Assign settings individually, in order to keep the current values
    // of settings not specified in $settings.
    foreach ($settings as $setting_name => $setting) {
      $this->getItemDefinition()->setSetting($setting_name, $setting);
    }
    return $this;
  }

}
