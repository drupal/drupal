<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Entity\Plugin\Validation\Constraint\BundleConstraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityTypeConstraint;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\TypedData\TypedDataTrait;

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

  /**
   * {@inheritdoc}
   */
  public function isSatisfiedBy(ContextInterface $context) {
    $definition = $context->getContextDefinition();
    // If the data types do not match, this context is invalid unless the
    // expected data type is any, which means all data types are supported.
    if ($this->getDataType() != 'any' && $definition->getDataType() != $this->getDataType()) {
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
    // @todo Move the entity specific logic out of this class in
    //   https://www.drupal.org/node/2932462.
    // Get the constraints from the context's definition.
    $constraints = $this->getConstraintObjects();
    // If constraints include EntityType, we generate an entity or adapter.
    if (!empty($constraints['EntityType']) && $constraints['EntityType'] instanceof EntityTypeConstraint) {
      $entity_type_manager = \Drupal::entityTypeManager();
      $entity_type_id = $constraints['EntityType']->type;
      $storage = $entity_type_manager->getStorage($entity_type_id);
      // If the storage can generate a sample entity we might delegate to that.
      if ($storage instanceof ContentEntityStorageInterface) {
        if (!empty($constraints['Bundle']) && $constraints['Bundle'] instanceof BundleConstraint) {
          foreach ($constraints['Bundle']->bundle as $bundle) {
            // We have a bundle, we are bundleable and we can generate a sample.
            yield EntityAdapter::createFromEntity($storage->createWithSampleValues($bundle));
          }
          return;
        }
      }

      // Either no bundle, or not bundleable, so generate an entity adapter.
      $definition = EntityDataDefinition::create($entity_type_id);
      yield new EntityAdapter($definition);
      return;
    }

    // No entity related constraints, so generate a basic typed data object.
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

    // @todo Move the entity specific logic out of this class in
    //   https://www.drupal.org/node/2932462.
    // If the data type is an entity, manually add one to the constraints array.
    if (strpos($this->getDataType(), 'entity:') === 0) {
      $entity_type_id = substr($this->getDataType(), 7);
      $constraint_definitions['EntityType'] = ['type' => $entity_type_id];
    }

    $validation_constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints = [];
    foreach ($constraint_definitions as $constraint_name => $constraint_definition) {
      $constraints[$constraint_name] = $validation_constraint_manager->create($constraint_name, $constraint_definition);
    }

    return $constraints;
  }

}
