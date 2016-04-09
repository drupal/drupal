<?php

namespace Drupal\Core\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * An entity field item.
 *
 * Entity field items making use of this base class have to implement
 * the static method propertyDefinitions().
 *
 * @see \Drupal\Core\Field\FieldItemInterface
 * @ingroup field_types
 */
abstract class FieldItemBase extends Map implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    // Initialize computed properties by default, such that they get cloned
    // with the whole item.
    foreach ($this->definition->getPropertyDefinitions() as $name => $definition) {
      if ($definition->isComputed()) {
        $this->properties[$name] = \Drupal::typedDataManager()->getPropertyInstance($this, $name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->getParent()->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->getParent()->getLangcode();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    return $this->definition->getFieldDefinition();
  }

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   The array of settings.
   */
  protected function getSettings() {
    return $this->getFieldDefinition()->getSettings();
  }

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSetting($setting_name) {
    return $this->getFieldDefinition()->getSetting($setting_name);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the first property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $keys = array_keys($this->definition->getPropertyDefinitions());
      $values = array($keys[0] => $values);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   *
   * Different to the parent Map class, we avoid creating property objects as
   * far as possible in order to optimize performance. Thus we just update
   * $this->values if no property object has been created yet.
   */
  protected function writePropertyValue($property_name, $value) {
    // For defined properties there is either a property object or a plain
    // value that needs to be updated.
    if (isset($this->properties[$property_name])) {
      $this->properties[$property_name]->setValue($value, FALSE);
    }
    // Allow setting plain values for not-defined properties also.
    else {
      $this->values[$property_name] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    // There is either a property object or a plain value - possibly for a
    // not-defined property. If we have a plain value, directly return it.
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue();
    }
    elseif (isset($this->values[$name])) {
      return $this->values[$name];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    // Support setting values via property objects, but take care in as the
    // value of the 'entity' property is typed data also.
    if ($value instanceof TypedDataInterface && !($value instanceof EntityInterface)) {
      $value = $value->getValue();
    }
    $this->set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue() !== NULL;
    }
    return isset($this->values[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    if ($this->definition->getPropertyDefinition($name)) {
      $this->set($name, NULL);
    }
    else {
      // Explicitly unset the property in $this->values if a non-defined
      // property is unset, such that its key is removed from $this->values.
      unset($this->values[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view($display_options = array()) {
    $view_builder = \Drupal::entityManager()->getViewBuilder($this->getEntity()->getEntityTypeId());
    return $view_builder->viewFieldItem($this, $display_options);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() { }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) { }

  /**
   * {@inheritdoc}
   */
  public function delete() { }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) { }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() { }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsToConfigData(array $settings) {
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsFromConfigData(array $settings) {
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsToConfigData(array $settings) {
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsFromConfigData(array $settings) {
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(FieldDefinitionInterface $field_definition, array $dependencies) {
    return FALSE;
  }

}
