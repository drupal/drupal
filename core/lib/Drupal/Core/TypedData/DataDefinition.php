<?php

namespace Drupal\Core\TypedData;

/**
 * A typed data definition class for defining data based on defined data types.
 */
class DataDefinition implements DataDefinitionInterface, \ArrayAccess {

  use TypedDataTrait;

  /**
   * The array holding values for all definition keys.
   *
   * @var array
   */
  protected $definition = [];

  /**
   * Creates a new data definition.
   *
   * @param string $type
   *   The data type of the data; e.g., 'string', 'integer' or 'any'.
   *
   * @return static
   *   A new DataDefinition object.
   */
  public static function create($type) {
    $definition['type'] = $type;
    return new static($definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($type) {
    return self::create($type);
  }

  /**
   * Constructs a new data definition object.
   *
   * @param array $values
   *   (optional) If given, an array of initial values to set on the definition.
   */
  public function __construct(array $values = []) {
    $this->definition = $values;
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
   * @return static
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
    return $this->definition['label'] ?? NULL;
  }

  /**
   * Sets the human-readable label.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label to set.
   *
   * @return static
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
    return $this->definition['description'] ?? NULL;
  }

  /**
   * Sets the human-readable description.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description to set.
   *
   * @return static
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
    return ($this instanceof ListDataDefinitionInterface);
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
   * @return static
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
   * @return static
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
   * @return static
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
    if (isset($this->definition['class'])) {
      return $this->definition['class'];
    }
    else {
      $type_definition = \Drupal::typedDataManager()->getDefinition($this->getDataType());
      return $type_definition['class'];
    }
  }

  /**
   * Sets the class used for creating the typed data object.
   *
   * @param string|null $class
   *   The class to use.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setClass($class) {
    $this->definition['class'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->definition['settings'] ?? [];
  }

  /**
   * Sets the array of settings, as required by the used class.
   *
   * @param array $settings
   *   The array of settings.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setSettings(array $settings) {
    $this->definition['settings'] = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    return $this->definition['settings'][$setting_name] ?? NULL;
  }

  /**
   * Sets a definition setting.
   *
   * @param string $setting_name
   *   The definition setting to set.
   * @param mixed $value
   *   The value to set.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setSetting($setting_name, $value) {
    $this->definition['settings'][$setting_name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = $this->definition['constraints'] ?? [];
    $constraints += $this->getTypedDataManager()->getDefaultConstraints($this);
    // If either the constraints defined on this data definition or the default
    // constraints for this data definition's type contain the `NotBlank`
    // constraint, then prevent a validation error from `NotBlank` if `NotNull`
    // already would generate one. (When both are present, `NotBlank` should
    // allow a NULL value, otherwise there will be two validation errors with
    // distinct messages for the exact same problem. Automatically configuring
    // `NotBlank`'s `allowNull: true` option mitigates that.)
    // @see ::isRequired()
    // @see \Drupal\Core\TypedData\TypedDataManager::getDefaultConstraints()
    if (array_key_exists('NotBlank', $constraints) && $this->isRequired()) {
      assert(array_key_exists('NotNull', $constraints));
      $constraints['NotBlank']['allowNull'] = TRUE;
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraint($constraint_name) {
    $constraints = $this->getConstraints();
    return $constraints[$constraint_name] ?? NULL;
  }

  /**
   * Sets an array of validation constraints.
   *
   * @param array $constraints
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   *
   * @return $this
   */
  public function setConstraints(array $constraints) {
    $this->definition['constraints'] = $constraints;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addConstraint($constraint_name, $options = NULL) {
    $this->definition['constraints'][$constraint_name] = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo Remove in https://www.drupal.org/node/1928868.
   */
  public function offsetExists($offset): bool {
    // PHP's array access does not work correctly with isset(), so we have to
    // bake isset() in here. See https://bugs.php.net/bug.php?id=41727.
    return array_key_exists($offset, $this->definition) && isset($this->definition[$offset]);
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo Remove in https://www.drupal.org/node/1928868.
   */
  public function &offsetGet($offset): mixed {
    if (!isset($this->definition[$offset])) {
      $this->definition[$offset] = NULL;
    }
    return $this->definition[$offset];
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo Remove in https://www.drupal.org/node/1928868.
   */
  public function offsetSet($offset, $value): void {
    $this->definition[$offset] = $value;
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo Remove in https://www.drupal.org/node/1928868.
   */
  public function offsetUnset($offset): void {
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
   * {@inheritdoc}
   */
  public function __sleep(): array {
    // Never serialize the typed data manager.
    $vars = get_object_vars($this);
    unset($vars['typedDataManager']);
    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    // Respect the definition, otherwise default to TRUE for computed fields.
    if (isset($this->definition['internal'])) {
      return $this->definition['internal'];
    }
    return $this->isComputed();
  }

  /**
   * Sets the whether the data value should be internal.
   *
   * @param bool $internal
   *   Whether the data value should be internal.
   *
   * @return $this
   *
   * @see \Drupal\Core\TypedData\DataDefinitionInterface::isInternal
   */
  public function setInternal($internal) {
    $this->definition['internal'] = $internal;
    return $this;
  }

}
