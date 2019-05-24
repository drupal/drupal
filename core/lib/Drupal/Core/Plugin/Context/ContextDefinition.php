<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\TypedData\TypedDataTrait;

/**
 * Defines a class for context definitions.
 */
class ContextDefinition implements ContextDefinitionInterface {

  use DependencySerializationTrait {
    __sleep as traitSleep;
  }

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
   * An EntityContextDefinition instance, for backwards compatibility.
   *
   * If this context is created with a data type that starts with 'entity:',
   * this property will be an instance of EntityContextDefinition, and certain
   * methods of this object will delegate to their overridden counterparts in
   * $this->entityContextDefinition.
   *
   * This property should be kept private so that it is only accessible to this
   * class for backwards compatibility reasons. It will be removed in Drupal 9.
   *
   * @deprecated
   *   Constructing a context definition for an entity type (i.e., the data type
   *   begins with 'entity:') is deprecated in Drupal 8.6.0. Instead, use
   *   the static factory methods of EntityContextDefinition to create context
   *   definitions for entity types, or the static ::create() method of this
   *   class for any other data type. See https://www.drupal.org/node/2976400
   *   for more information.
   *
   * @see ::__construct()
   * @see ::__sleep()
   * @see ::__wakeup()
   * @see ::getConstraintObjects()
   * @see ::getSampleValues()
   * @see ::initializeEntityContextDefinition()
   * @see https://www.drupal.org/node/2932462
   * @see https://www.drupal.org/node/2976400
   *
   * @var \Drupal\Core\Plugin\Context\EntityContextDefinition
   */
  private $entityContextDefinition;

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
   */
  public function __construct($data_type = 'any', $label = NULL, $required = TRUE, $multiple = FALSE, $description = NULL, $default_value = NULL) {
    $this->dataType = $data_type;
    $this->label = $label;
    $this->isRequired = $required;
    $this->isMultiple = $multiple;
    $this->description = $description;
    $this->defaultValue = $default_value;

    if (strpos($data_type, 'entity:') === 0 && !($this instanceof EntityContextDefinition)) {
      @trigger_error('Constructing a ContextDefinition object for an entity type is deprecated in Drupal 8.6.0. Use ' . __NAMESPACE__ . '\EntityContextDefinition instead. See https://www.drupal.org/node/2976400 for more information.', E_USER_DEPRECATED);
      $this->initializeEntityContextDefinition();
    }
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
    // If the backwards compatibility layer is present, delegate to that.
    $this->initializeEntityContextDefinition();
    if ($this->entityContextDefinition) {
      return $this->entityContextDefinition->getConstraints();
    }

    // @todo Apply defaults.
    return $this->constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraint($constraint_name) {
    // If the backwards compatibility layer is present, delegate to that.
    $this->initializeEntityContextDefinition();
    if ($this->entityContextDefinition) {
      return $this->entityContextDefinition->getConstraint($constraint_name);
    }

    $constraints = $this->getConstraints();
    return isset($constraints[$constraint_name]) ? $constraints[$constraint_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setConstraints(array $constraints) {
    // If the backwards compatibility layer is present, delegate to that.
    $this->initializeEntityContextDefinition();
    if ($this->entityContextDefinition) {
      $this->entityContextDefinition->setConstraints($constraints);
    }

    $this->constraints = $constraints;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addConstraint($constraint_name, $options = NULL) {
    // If the backwards compatibility layer is present, delegate to that.
    $this->initializeEntityContextDefinition();
    if ($this->entityContextDefinition) {
      $this->entityContextDefinition->addConstraint($constraint_name, $options);
    }

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
      strpos($that_type, "$this_type:") === 0
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
      $this->initializeEntityContextDefinition();
      if ($this->entityContextDefinition) {
        $values = $this->entityContextDefinition->getSampleValues();
      }
      else {
        $values = $definition->getSampleValues();
      }
    }
    else {
      $values = [];
    }

    $validator = $this->getTypedDataManager()->getValidator();
    foreach ($values as $value) {
      $constraints = array_values($this->getConstraintObjects());
      $violations = $validator->validate($value, $constraints);
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
    // If the backwards compatibility layer is present, delegate to that.
    $this->initializeEntityContextDefinition();
    if ($this->entityContextDefinition) {
      return $this->entityContextDefinition->getConstraintObjects();
    }

    $constraint_definitions = $this->getConstraints();

    $validation_constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints = [];
    foreach ($constraint_definitions as $constraint_name => $constraint_definition) {
      $constraints[$constraint_name] = $validation_constraint_manager->create($constraint_name, $constraint_definition);
    }

    return $constraints;
  }

  /**
   * Implements magic __sleep() method.
   */
  public function __sleep() {
    return array_diff($this->traitSleep(), ['entityContextDefinition']);
  }

  /**
   * Initializes $this->entityContextDefinition for backwards compatibility.
   *
   * This method should be kept private so that it is only accessible to this
   * class for backwards compatibility reasons. It will be removed in Drupal 9.
   *
   * @deprecated
   */
  private function initializeEntityContextDefinition() {
    if (!$this instanceof EntityContextDefinition && strpos($this->getDataType(), 'entity:') === 0 && !$this->entityContextDefinition) {
      $this->entityContextDefinition = EntityContextDefinition::create()
        ->setDataType($this->getDataType())
        ->setLabel($this->getLabel())
        ->setRequired($this->isRequired())
        ->setMultiple($this->isMultiple())
        ->setDescription($this->getDescription())
        ->setConstraints($this->constraints)
        ->setDefaultValue($this->getDefaultValue());
    }
  }

}
