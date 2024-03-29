<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\TypedData\TypedDataTrait;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Defines a class for context definitions.
 */
class ContextDefinition implements ContextDefinitionInterface {

  use DependencySerializationTrait;
  use TypedDataTrait;

  /**
   * The data type of the data.
   *
   * @var string
   *   The data type.
   */
  protected $dataType;

  /**
   * The human-readable label.
   *
   * @var string
   *   The label.
   */
  protected $label;

  /**
   * The human-readable description.
   *
   * @var string|null
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
    if (str_starts_with($data_type, 'entity:')) {
      return new EntityContextDefinition($data_type);
    }
    return new static(
      $data_type
    );
  }

  /**
   * Constructs a new context definition object.
   *
   * @param string $data_type
   *   The required data type.
   * @param string|null $label
   *   The label of this context definition for the UI.
   * @param bool $required
   *   Whether the context definition is required.
   * @param bool $multiple
   *   Whether the context definition is multivalue.
   * @param string|null $description
   *   The description of this context definition for the UI.
   * @param mixed $default_value
   *   The default value of this definition.
   * @param array $constraints
   *   An array of constraints keyed by the constraint name and a value of an
   *   array constraint options or a NULL.
   */
  public function __construct($data_type = 'any', $label = NULL, $required = TRUE, $multiple = FALSE, $description = NULL, $default_value = NULL, array $constraints = []) {
    $this->dataType = $data_type;
    $this->label = $label;
    $this->isRequired = $required;
    $this->isMultiple = $multiple;
    $this->description = $description;
    $this->defaultValue = $default_value;
    foreach ($constraints as $constraint_name => $options) {
      $this->addConstraint($constraint_name, $options);
    }

    assert(!str_starts_with($data_type, 'entity:') || $this instanceof EntityContextDefinition);
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
    return $constraints[$constraint_name] ?? NULL;
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

  /**
   * Checks if this definition's data type matches that of the given context.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context to test against.
   *
   * @return bool
   *   TRUE if the data types match, otherwise FALSE.
   */
  protected function dataTypeMatches(ContextInterface $context) {
    $this_type = $this->getDataType();
    $that_type = $context->getContextDefinition()->getDataType();

    return (
      // 'any' means all data types are supported.
      $this_type === 'any' ||
      $this_type === $that_type ||
      // Allow a more generic data type like 'entity' to be fulfilled by a more
      // specific data type like 'entity:user'. However, if this type is more
      // specific, do not consider a more generic type to be a match.
      str_starts_with($that_type, "$this_type:")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isSatisfiedBy(ContextInterface $context) {
    $definition = $context->getContextDefinition();
    if (!$this->dataTypeMatches($context)) {
      return FALSE;
    }

    // Get the value for this context, either directly if possible or by
    // introspecting the definition.
    if ($context->hasContextValue()) {
      $values = [$context->getContextData()];
    }
    elseif ($definition instanceof self) {
      $values = $definition->getSampleValues();
    }
    else {
      $values = [];
    }

    $validator = $this->getTypedDataManager()->getValidator();
    foreach ($values as $value) {
      $constraints = array_values($this->getConstraintObjects());
      if ($definition->isMultiple()) {
        $violations = new ConstraintViolationList();
        foreach ($value as $item) {
          $violations->addAll($validator->validate($item, $constraints));
        }
      }
      else {
        $violations = $validator->validate($value, $constraints);
      }
      foreach ($violations as $delta => $violation) {
        // Remove any violation that does not correspond to the constraints.
        if (!in_array($violation->getConstraint(), $constraints)) {
          $violations->remove($delta);
        }
      }
      // If a value has no violations then the requirement is satisfied.
      if (!$violations->count()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns typed data objects representing this context definition.
   *
   * This should return as many objects as needed to reflect the variations of
   * the constraints it supports.
   *
   * @yield \Drupal\Core\TypedData\TypedDataInterface
   *   The set of typed data object.
   */
  protected function getSampleValues() {
    yield $this->getTypedDataManager()->create($this->getDataDefinition());
  }

  /**
   * Extracts an array of constraints for a context definition object.
   *
   * @return \Symfony\Component\Validator\Constraint[]
   *   A list of applied constraints for the context definition.
   */
  protected function getConstraintObjects() {
    $constraint_definitions = $this->getConstraints();

    $validation_constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints = [];
    foreach ($constraint_definitions as $constraint_name => $constraint_definition) {
      $constraints[$constraint_name] = $validation_constraint_manager->create($constraint_name, $constraint_definition);
    }

    return $constraints;
  }

}
