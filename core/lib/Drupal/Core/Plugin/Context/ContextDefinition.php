<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Core\TypedData\TypedDataTrait;

/**
 * Defines a class for context definitions.
 */
class ContextDefinition implements ContextDefinitionInterface {

  use TypedDataTrait;

  /**
   * The data type of the data.
   *
   * @return string
   *   The data type.
   */
  protected $dataType;

  /**
   * The human-readable label.
   *
   * @return string
   *   The label.
   */
  protected $label;

  /**
   * The human-readable description.
   *
   * @return string|null
   *   The description, or NULL if no description is available.
   */
  protected $description;

  /**
   * Whether the data is multi-valued, i.e. a list of data items.
   *
   * @var bool
   */
  protected $isMultiple = FALSE;

  /**
   * Determines whether a data value is required.
   *
   * @var bool
   *   Whether a data value is required.
   */
  protected $isRequired = TRUE;

  /**
   * The default value.
   *
   * @var mixed
   */
  protected $defaultValue;

  /**
   * An array of constraints.
   *
   * @var array[]
   */
  protected $constraints = [];

  /**
   * Creates a new context definition.
   *
   * @param string $data_type
   *   The data type for which to create the context definition. Defaults to
   *   'any'.
   *
   * @return static
   *   The created context definition object.
   */
  public static function create($data_type = 'any') {
    return new static(
      $data_type
    );
  }

  /**
   * Constructs a new context definition object.
   *
   * @param string $data_type
   *   The required data type.
   * @param mixed string|null $label
   *   The label of this context definition for the UI.
   * @param bool $required
   *   Whether the context definition is required.
   * @param bool $multiple
   *   Whether the context definition is multivalue.
   * @param string|null $description
   *   The description of this context definition for the UI.
   * @param mixed $default_value
   *   The default value of this definition.
   */
  public function __construct($data_type = 'any', $label = NULL, $required = TRUE, $multiple = FALSE, $description = NULL, $default_value = NULL) {
    $this->dataType = $data_type;
    $this->label = $label;
    $this->isRequired = $required;
    $this->isMultiple = $multiple;
    $this->description = $description;
    $this->defaultValue = $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    return $this->dataType;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataType($data_type) {
    $this->dataType = $data_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
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
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    return $this->isMultiple;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple($multiple = TRUE) {
    $this->isMultiple = $multiple;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->isRequired;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequired($required = TRUE) {
    $this->isRequired = $required;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return $this->defaultValue;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue($default_value) {
    $this->defaultValue = $default_value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    // @todo Apply defaults.
    return $this->constraints;
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
  public function setConstraints(array $constraints) {
    $this->constraints = $constraints;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addConstraint($constraint_name, $options = NULL) {
    $this->constraints[$constraint_name] = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition() {
    if ($this->isMultiple()) {
      $definition = $this->getTypedDataManager()->createListDataDefinition($this->getDataType());
    }
    else {
      $definition = $this->getTypedDataManager()->createDataDefinition($this->getDataType());
    }

    if (!$definition) {
      throw new \Exception("The data type '{$this->getDataType()}' is invalid");
    }
    $definition->setLabel($this->getLabel())
      ->setDescription($this->getDescription())
      ->setRequired($this->isRequired());
    $constraints = $definition->getConstraints() + $this->getConstraints();
      $definition->setConstraints($constraints);
    return $definition;
  }

}
