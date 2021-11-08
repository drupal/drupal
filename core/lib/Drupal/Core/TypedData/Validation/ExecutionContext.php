<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\Validation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Defines an execution context class.
 *
 * We do not use the context provided by Symfony as it is marked internal, so
 * this class is pretty much the same, but has some code style changes as well
 * as exceptions for methods we don't support.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class
 *   \Symfony\Component\Validator\Context\ExecutionContext instead.
 *
 * @see https://www.drupal.org/node/3238432
 */
class ExecutionContext implements ExecutionContextInterface {

  /**
   * @var \Symfony\Component\Validator\Validator\ValidatorInterface
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
  protected $validatedObjects = [];

  /**
   * Stores which class constraint has been validated for which object.
   *
   * @var array
   */
  protected $validatedConstraints = [];

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
    @trigger_error(__CLASS__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class \Symfony\Component\Validator\Context\ExecutionContext instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    $this->validator = $validator;
    $this->root = $root;
    $this->translator = $translator;
    $this->translationDomain = $translationDomain;
    $this->violations = new ConstraintViolationList();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::setNode()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function setNode($value, $object, MetadataInterface $metadata = NULL, $propertyPath) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::setNode() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    $this->value = $value;
    $this->data = $object;
    $this->metadata = $metadata;
    $this->propertyPath = (string) $propertyPath;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::setGroup()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function setGroup($group) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::setGroup() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    $this->group = $group;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::setConstraint()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function setConstraint(Constraint $constraint) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::setConstraint() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    $this->constraint = $constraint;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::addViolation()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function addViolation($message, array $parameters = [], $invalidValue = NULL, $plural = NULL, $code = NULL) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::addViolation() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
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
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::buildViolation()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function buildViolation($message, array $parameters = []): ConstraintViolationBuilderInterface {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::buildViolation() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return new ConstraintViolationBuilder($this->violations, $this->constraint, $message, $parameters, $this->root, $this->propertyPath, $this->value, $this->translator, $this->translationDomain);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getViolations()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getViolations() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getViolations() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return $this->violations;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getValidator()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getValidator() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getValidator() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return $this->validator;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getRoot()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getRoot() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getRoot() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return $this->root;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getValue()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getValue() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getValue() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return $this->value;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getObject()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getObject(): ?object {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getObject() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return $this->data;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getMetadata()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getMetadata(): ?MetadataInterface {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getMetadata() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return $this->metadata;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getGroup()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getGroup(): ?string {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getGroup() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return Constraint::DEFAULT_GROUP;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getClassName()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getClassName(): ?string {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getClassName() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return get_class($this->data);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getPropertyName()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getPropertyName(): ?string {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getPropertyName() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return $this->data->getName();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getPropertyPath()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getPropertyPath($sub_path = ''): string {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getPropertyPath() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return PropertyPath::append($this->propertyPath, $sub_path);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::addViolationAt()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function addViolationAt($subPath, $message, array $parameters = [], $invalidValue = NULL, $plural = NULL, $code = NULL) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::addViolationAt() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    throw new \LogicException('Legacy validator API is unsupported.');
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::validate()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function validate($value, $subPath = '', $groups = NULL, $traverse = FALSE, $deep = FALSE) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::validate() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    throw new \LogicException('Legacy validator API is unsupported.');
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::markConstraintAsValidated()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function markConstraintAsValidated($cache_key, $constraint_hash) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::markConstraintAsValidated() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    $this->validatedConstraints[$cache_key . ':' . $constraint_hash] = TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::isConstraintValidated()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function isConstraintValidated($cache_key, $constraint_hash) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::isConstraintValidated() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return isset($this->validatedConstraints[$cache_key . ':' . $constraint_hash]);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::validateValue()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function validateValue($value, $constraints, $subPath = '', $groups = NULL) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::validateValue() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    throw new \LogicException('Legacy validator API is unsupported.');
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::markGroupAsValidated()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function markGroupAsValidated($cache_key, $group_hash) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::markGroupAsValidated() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    $this->validatedObjects[$cache_key][$group_hash] = TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::isGroupValidated()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function isGroupValidated($cache_key, $group_hash) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::isGroupValidated() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    return isset($this->validatedObjects[$cache_key][$group_hash]);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::markObjectAsInitialized()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function markObjectAsInitialized($cache_key) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::markObjectAsInitialized() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    // Not supported, so nothing todo.
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::isObjectInitialized()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function isObjectInitialized($cache_key) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::isObjectInitialized() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    // Not supported, so nothing todo.
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Symfony\Component\Validator\Context\ExecutionContext::getMetadataFactory()
   *   instead.
   *
   * @see https://www.drupal.org/node/3238432
   */
  public function getMetadataFactory() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Context\ExecutionContext::getMetadataFactory() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
    throw new \LogicException('Legacy validator API is unsupported.');
  }

}
