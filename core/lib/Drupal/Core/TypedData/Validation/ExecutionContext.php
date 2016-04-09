<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\Validation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Defines an execution context class.
 *
 * We do not use the context provided by Symfony as it is marked internal, so
 * this class is pretty much the same, but has some code style changes as well
 * as exceptions for methods we don't support.
 */
class ExecutionContext implements ExecutionContextInterface {

  /**
   * @var \Symfony\Component\Validator\ValidatorInterface
   */
  protected $validator;

  /**
   * The root value of the validated object graph.
   *
   * @var mixed
   */
  protected $root;

  /**
   * @var \Drupal\Core\Validation\TranslatorInterface
   */
  protected $translator;

  /**
   * @var string
   */
  protected $translationDomain;

  /**
   * The violations generated in the current context.
   *
   * @var \Symfony\Component\Validator\ConstraintViolationList
   */
  protected $violations;

  /**
   * The currently validated value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The currently validated typed data object.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $data;

  /**
   * The property path leading to the current value.
   *
   * @var string
   */
  protected $propertyPath = '';

  /**
   * The current validation metadata.
   *
   * @var \Symfony\Component\Validator\Mapping\MetadataInterface|null
   */
  protected $metadata;

  /**
   * The currently validated group.
   *
   * @var string|null
   */
  protected $group;

  /**
   * The currently validated constraint.
   *
   * @var \Symfony\Component\Validator\Constraint|null
   */
  protected $constraint;

  /**
   * Stores which objects have been validated in which group.
   *
   * @var array
   */
  protected $validatedObjects = array();

  /**
   * Stores which class constraint has been validated for which object.
   *
   * @var array
   */
  protected $validatedConstraints = array();

  /**
   * Creates a new ExecutionContext.
   *
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   The validator.
   * @param mixed $root
   *   The root.
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator.
   * @param string $translationDomain
   *   (optional) The translation domain.
   *
   * @internal Called by \Drupal\Core\TypedData\Validation\ExecutionContextFactory.
   *    Should not be used in user code.
   */
  public function __construct(ValidatorInterface $validator, $root, TranslatorInterface $translator, $translationDomain = NULL) {
    $this->validator = $validator;
    $this->root = $root;
    $this->translator = $translator;
    $this->translationDomain = $translationDomain;
    $this->violations = new ConstraintViolationList();
  }

  /**
   * {@inheritdoc}
   */
  public function setNode($value, $object, MetadataInterface $metadata = NULL, $propertyPath) {
    $this->value = $value;
    $this->data = $object;
    $this->metadata = $metadata;
    $this->propertyPath = (string) $propertyPath;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group) {
    $this->group = $group;
  }

  /**
   * {@inheritdoc}
   */
  public function setConstraint(Constraint $constraint) {
    $this->constraint = $constraint;
  }

  /**
   * {@inheritdoc}
   */
  public function addViolation($message, array $parameters = array(), $invalidValue = NULL, $plural = NULL, $code = NULL) {
    // The parameters $invalidValue and following are ignored by the new
    // API, as they are not present in the new interface anymore.
    // You should use buildViolation() instead.
    if (func_num_args() > 2) {
      throw new \LogicException('Legacy validator API is unsupported.');
    }

    $this->violations->add(new ConstraintViolation($this->translator->trans($message, $parameters, $this->translationDomain), $message, $parameters, $this->root, $this->propertyPath, $this->value, NULL, NULL, $this->constraint));
  }

  /**
   * {@inheritdoc}
   */
  public function buildViolation($message, array $parameters = array()) {
    return new ConstraintViolationBuilder($this->violations, $this->constraint, $message, $parameters, $this->root, $this->propertyPath, $this->value, $this->translator, $this->translationDomain);
  }

  /**
   * {@inheritdoc}
   */
  public function getViolations() {
    return $this->violations;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidator() {
    return $this->validator;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoot() {
    return $this->root;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getObject() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    return $this->metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return Constraint::DEFAULT_GROUP;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassName() {
    return get_class($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyName() {
    return $this->data->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyPath($sub_path = '') {
    return PropertyPath::append($this->propertyPath, $sub_path);
  }

  /**
   * {@inheritdoc}
   */
  public function addViolationAt($subPath, $message, array $parameters = array(), $invalidValue = NULL, $plural = NULL, $code = NULL) {
    throw new \LogicException('Legacy validator API is unsupported.');
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, $subPath = '', $groups = NULL, $traverse = FALSE, $deep = FALSE) {
    throw new \LogicException('Legacy validator API is unsupported.');
  }

  /**
   * {@inheritdoc}
   */
  public function markConstraintAsValidated($cache_key, $constraint_hash) {
    $this->validatedConstraints[$cache_key . ':' . $constraint_hash] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isConstraintValidated($cache_key, $constraint_hash) {
    return isset($this->validatedConstraints[$cache_key . ':' . $constraint_hash]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateValue($value, $constraints, $subPath = '', $groups = NULL) {
    throw new \LogicException('Legacy validator API is unsupported.');
  }

  /**
   * {@inheritdoc}
   */
  public function markGroupAsValidated($cache_key, $group_hash) {
    $this->validatedObjects[$cache_key][$group_hash] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isGroupValidated($cache_key, $group_hash) {
    return isset($this->validatedObjects[$cache_key][$group_hash]);
  }

  /**
   * {@inheritdoc}
   */
  public function markObjectAsInitialized($cache_key) {
    // Not supported, so nothing todo.
  }

  /**
   * {@inheritdoc}
   */
  public function isObjectInitialized($cache_key) {
    // Not supported, so nothing todo.
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataFactory() {
    throw new \LogicException('Legacy validator API is unsupported.');
  }
}
