<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\DataDefinition.
 */

namespace Drupal\Core\TypedData;

/**
 * A class for defining data based on defined data types.
 */
class DataDefinition implements DataDefinitionInterface, \ArrayAccess {

  /**
   * The array holding values for all definition keys.
   *
   * @var array
   */
  protected $definition = array();

  /**
   * Creates a new data definition.
   *
   * @param string $type
   *   The data type of the data; e.g., 'string', 'integer' or 'any'.
   *
   * @return \Drupal\Core\TypedData\DataDefinition
   *   A new DataDefinition object.
   */
  public static function create($type) {
    $definition['type'] = $type;
    return new static($definition);
  }

  /**
   * Constructs a new data definition object.
   *
   * @param array $definition
   *   (optional) If given, a data definition represented as array.
   */
  public function __construct(array $definition = array()) {
    $this->definition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    return !empty($this->definition['type']) ? $this->definition['type'] : 'any';
  }

  /**
   * Sets the data type.
   *
   * @param string $type
   *   The data type to set.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setDataType($type) {
    $this->definition['type'] = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->definition['label']) ? $this->definition['label'] : NULL;
  }

  /**
   * Sets the human-readable label.
   *
   * @param string $label
   *   The label to set.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setLabel($label) {
    $this->definition['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->definition['description']) ? $this->definition['description'] : NULL;
  }

  /**
   * Sets the human-readable description.
   *
   * @param string $description
   *   The description to set.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setDescription($description) {
    $this->definition['description'] = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isList() {
    return ($this instanceof ListDefinitionInterface);
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    if (!isset($this->definition['read-only'])) {
      // Default to read-only if the data value is computed.
      return $this->isComputed();
    }
    return !empty($this->definition['read-only']);
  }

  /**
   * Sets whether the data is read-only.
   *
   * @param bool $read_only
   *   Whether the data is read-only.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setReadOnly($read_only) {
    $this->definition['read-only'] = $read_only;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return !empty($this->definition['computed']);
  }

  /**
   * Sets whether the data is computed.
   *
   * @param bool $computed
   *   Whether the data is computed.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setComputed($computed) {
    $this->definition['computed'] = $computed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return !empty($this->definition['required']);
  }

  /**
   * Sets whether the data is required.
   *
   * @param bool $required
   *   Whether the data is required.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setRequired($required) {
    $this->definition['required'] = $required;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return isset($this->definition['class']) ? $this->definition['class'] : NULL;
  }

  /**
   * Sets the class used for creating the typed data object.
   *
   * @param string|null $class
   *   The class to use.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setClass($class) {
    $this->definition['class'] = $class;
    return $this;
  }

  /**
   * Returns the array of settings, as required by the used class.
   *
   * See the documentation of the class for supported or required settings.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings() {
    return isset($this->definition['settings']) ? $this->definition['settings'] : array();
  }

  /**
   * Sets the array of settings, as required by the used class.
   *
   * @param array $settings
   *   The array of settings.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setSettings(array $settings) {
    $this->definition['settings'] = $settings;
    return $this;
  }

  /**
   * Returns an array of validation constraints.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @return array
   *   Array of constraints, each being an instance of
   *   \Symfony\Component\Validator\Constraint.
   */
  public function getConstraints() {
    return isset($this->definition['constraints']) ? $this->definition['constraints'] : array();
  }

  /**
   * Sets the array of validation constraints.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @param array $constraints
   *   The array of constraints.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setConstraints(array $constraints) {
    $this->definition['constraints'] = $constraints;
    return $this;
  }

  /**
   * Adds a validation constraint.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @param string $constraint_name
   *   The name of the constraint to add, i.e. its plugin id.
   * @param array|null $options
   *   The constraint options as required by the constraint plugin, or NULL.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function addConstraint($constraint_name, $options = NULL) {
    $this->definition['constraints'][$constraint_name] = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public function offsetExists($offset) {
    // PHP's array access does not work correctly with isset(), so we have to
    // bake isset() in here. See https://bugs.php.net/bug.php?id=41727.
    return array_key_exists($offset, $this->definition) && isset($this->definition[$offset]);
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public function &offsetGet($offset) {
    if (!isset($this->definition[$offset])) {
      $this->definition[$offset] = NULL;
    }
    return $this->definition[$offset];
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public function offsetSet($offset, $value) {
    $this->definition[$offset] = $value;
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public function offsetUnset($offset) {
    unset($this->definition[$offset]);
  }

  /**
   * Returns all definition values as array.
   *
   * @return array
   */
  public function toArray() {
    return $this->definition;
  }

  /**
   * Allows creating data definition objects from old style definition arrays.
   *
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public static function createFromOldStyleDefinition(array $definition) {
    if (empty($definition['list'])) {
      return new DataDefinition($definition);
    }

    // If the definition describes a list, separate the list item definition
    // from the list definition.
    unset($definition['list']);

    $list_definition = $definition;
    unset($list_definition['type']);

    // Constraints, class and settings apply to the list item.
    unset($list_definition['constraints']);
    unset($list_definition['class']);
    unset($list_definition['settings']);

    $list_definition = new ListDefinition($list_definition);
    if (isset($definition['list_class'])) {
      $list_definition->setClass($definition['list_class']);
    }
    else {
      $type_definition = \Drupal::typedData()->getDefinition($definition['type']);
      if (isset($type_definition['list_class'])) {
        $list_definition->setClass($type_definition['list_class']);
      }
    }

    // Take care of the item definition now.
    // Required applies to the list definition only.
    unset($definition['required']);
    $item_definition = new DataDefinition($definition);
    $list_definition->setItemDefinition($item_definition);
    return $list_definition;
  }
}
