<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Util\PropertyPath;

/**
 * Defines a recursive contextual validator for Typed Data.
 *
 * For both list and complex data it call recursively out to the properties /
 * elements of the list.
 *
 * This class calls out to some methods on the execution context marked as
 * internal. These methods are internal to the validator (which is implemented
 * by this class) but should not be called by users.
 * See http://symfony.com/doc/current/contributing/code/bc.html for more
 * information about @internal.
 *
 * @see \Drupal\Core\TypedData\Validation\RecursiveValidator::startContext()
 * @see \Drupal\Core\TypedData\Validation\RecursiveValidator::inContext()
 */
class RecursiveContextualValidator implements ContextualValidatorInterface {

  /**
   * The execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * The metadata factory.
   *
   * @var \Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface
   */
  protected $metadataFactory;

  /**
   * The constraint validator factory.
   *
   * @var \Symfony\Component\Validator\ConstraintValidatorFactoryInterface
   */
  protected $constraintValidatorFactory;

  /**
   * The typed data manager.
   */
  protected $typedDataManager;

  /**
   * Creates a validator for the given context.
   *
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The factory for creating new contexts.
   * @param \Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface $metadata_factory
   *   The metadata factory.
   * @param \Symfony\Component\Validator\ConstraintValidatorFactoryInterface $validator_factory
   *   The constraint validator factory.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   */
  public function __construct(ExecutionContextInterface $context, MetadataFactoryInterface $metadata_factory, ConstraintValidatorFactoryInterface $validator_factory, TypedDataManagerInterface $typed_data_manager) {
    $this->context = $context;
    $this->metadataFactory = $metadata_factory;
    $this->constraintValidatorFactory = $validator_factory;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function atPath($path) {
    // @todo This method is not used at the moment, see
    //   https://www.drupal.org/node/2482527
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($data, $constraints = NULL, $groups = NULL, $is_root_call = TRUE) {
    if (isset($groups)) {
      throw new \LogicException('Passing custom groups is not supported.');
    }

    if (!$data instanceof TypedDataInterface) {
      throw new \InvalidArgumentException('The passed value must be a typed data object.');
    }

    // You can pass a single constraint or an array of constraints.
    // Make sure to deal with an array in the rest of the code.
    if (isset($constraints) && !is_array($constraints)) {
      $constraints = [$constraints];
    }

    $this->validateNode($data, $constraints, $is_root_call);
    return $this;
  }

  /**
   * Validates a Typed Data node in the validation tree.
   *
   * If no constraints are passed, the data is validated against the
   * constraints specified in its data definition. If the data is complex or a
   * list and no constraints are passed, the contained properties or list items
   * are validated recursively.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The data to validated.
   * @param \Symfony\Component\Validator\Constraint[]|null $constraints
   *   (optional) If set, an array of constraints to validate.
   * @param bool $is_root_call
   *   (optional) Whether its the most upper call in the type data tree.
   *
   * @return $this
   */
  protected function validateNode(TypedDataInterface $data, $constraints = NULL, $is_root_call = FALSE) {
    $previous_value = $this->context->getValue();
    $previous_object = $this->context->getObject();
    $previous_metadata = $this->context->getMetadata();
    $previous_path = $this->context->getPropertyPath();

    $metadata = $this->metadataFactory->getMetadataFor($data);
    $cache_key = spl_object_hash($data);
    $property_path = $is_root_call ? '' : PropertyPath::append($previous_path, $data->getName());

    // Prefer a specific instance of the typed data manager stored by the data
    // if it is available. This is necessary for specialized typed data objects,
    // for example those using the typed config subclass of the manager.
    $typed_data_manager = method_exists($data, 'getTypedDataManager') ? $data->getTypedDataManager() : $this->typedDataManager;

    // Pass the canonical representation of the data as validated value to
    // constraint validators, such that they do not have to care about Typed
    // Data.
    $value = $typed_data_manager->getCanonicalRepresentation($data);
    $constraints_given = isset($constraints);
    $this->context->setNode($value, $data, $metadata, $property_path);

    if (isset($constraints) || !$this->context->isGroupValidated($cache_key, Constraint::DEFAULT_GROUP)) {
      if (!isset($constraints)) {
        $this->context->markGroupAsValidated($cache_key, Constraint::DEFAULT_GROUP);
        $constraints = $metadata->findConstraints(Constraint::DEFAULT_GROUP);
      }
      $this->validateConstraints($value, $cache_key, $constraints);
    }

    // If the data is a list or complex data, validate the contained list items
    // or properties. However, do not recurse if the data is empty.
    // Next, we do not recurse if given constraints are validated against an
    // entity, since we should determine whether the entity matches the
    // constraints and not whether the entity validates.
    if (($data instanceof ListInterface || $data instanceof ComplexDataInterface) && !$data->isEmpty() && !($data instanceof EntityAdapter && $constraints_given)) {
      foreach ($data as $property) {
        $this->validateNode($property);
      }
    }

    $this->context->setNode($previous_value, $previous_object, $previous_metadata, $previous_path);

    return $this;
  }

  /**
   * Validates a node's value against all constraints in the given group.
   *
   * @param mixed $value
   *   The validated value.
   * @param string $cache_key
   *   The cache key used internally to ensure we don't validate the same
   *   constraint twice.
   * @param \Symfony\Component\Validator\Constraint[] $constraints
   *   The constraints which should be ensured for the given value.
   */
  protected function validateConstraints($value, $cache_key, $constraints) {
    foreach ($constraints as $constraint) {
      // Prevent duplicate validation of constraints, in the case
      // that constraints belong to multiple validated groups
      if (isset($cache_key)) {
        $constraint_hash = spl_object_hash($constraint);

        if ($this->context->isConstraintValidated($cache_key, $constraint_hash)) {
          continue;
        }

        $this->context->markConstraintAsValidated($cache_key, $constraint_hash);
      }

      $this->context->setConstraint($constraint);

      $validator = $this->constraintValidatorFactory->getInstance($constraint);
      $validator->initialize($this->context);
      $validator->validate($value, $constraint);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getViolations() {
    return $this->context->getViolations();
  }

  /**
   * {@inheritdoc}
   */
  public function validateProperty($object, $propertyName, $groups = NULL) {
    if (isset($groups)) {
      throw new \LogicException('Passing custom groups is not supported.');
    }
    if (!is_object($object)) {
      throw new \InvalidArgumentException('Passing class name is not supported.');
    }
    elseif (!$object instanceof TypedDataInterface) {
      throw new \InvalidArgumentException('The passed in object has to be typed data.');
    }
    elseif (!$object instanceof ListInterface && !$object instanceof ComplexDataInterface) {
      throw new \InvalidArgumentException('Passed data does not contain properties.');
    }
    return $this->validateNode($object->get($propertyName), NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function validatePropertyValue($object, $property_name, $value, $groups = NULL) {
    if (!is_object($object)) {
      throw new \InvalidArgumentException('Passing class name is not supported.');
    }
    elseif (!$object instanceof TypedDataInterface) {
      throw new \InvalidArgumentException('The passed in object has to be typed data.');
    }
    elseif (!$object instanceof ListInterface && !$object instanceof ComplexDataInterface) {
      throw new \InvalidArgumentException('Passed data does not contain properties.');
    }
    $data = $object->get($property_name);
    $metadata = $this->metadataFactory->getMetadataFor($data);
    $constraints = $metadata->findConstraints(Constraint::DEFAULT_GROUP);
    return $this->validate($value, $constraints, $groups, TRUE);
  }

}
