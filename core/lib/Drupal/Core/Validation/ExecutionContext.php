<?php

namespace Drupal\Core\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Defines an execution context class.
 *
 * We do not use the context provided by Symfony as it is marked internal, so
 * this class is pretty much the same, but has some code style changes as well
 * as exceptions for methods we don't support.
 */
class ExecutionContext implements ExecutionContextInterface {

  /**
   * The violations generated in the current context.
   */
  protected ConstraintViolationList $violations;

  /**
   * The currently validated value.
   */
  protected mixed $value = NULL;

  /**
   * The currently validated object.
   */
  protected ?object $object = NULL;

  /**
   * The property path leading to the current value.
   */
  protected string $propertyPath = '';

  /**
   * The current validation metadata.
   */
  protected ?MetadataInterface $metadata = NULL;

  /**
   * The currently validated group.
   */
  protected ?string $group;

  /**
   * The currently validated constraint.
   */
  protected ?Constraint $constraint;

  /**
   * Stores which objects have been validated in which group.
   */
  protected array $validatedObjects = [];

  /**
   * Stores which class constraint has been validated for which object.
   */
  protected array $validatedConstraints = [];

  /**
   * Creates a new ExecutionContext.
   *
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   The validator.
   * @param mixed $root
   *   The root.
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator.
   * @param string|null $translationDomain
   *   (optional) The translation domain.
   *
   * @internal Called by \Drupal\Core\Validation\ExecutionContextFactory.
   *    Should not be used in user code.
   */
  public function __construct(
    protected ValidatorInterface $validator,
    protected mixed $root,
    protected TranslatorInterface $translator,
    protected ?string $translationDomain = NULL,
  ) {
    $this->violations = new ConstraintViolationList();
  }

  /**
   * {@inheritdoc}
   */
  public function setNode(mixed $value, ?object $object, ?MetadataInterface $metadata, string $propertyPath): void {
    $this->value = $value;
    $this->object = $object;
    $this->metadata = $metadata;
    $this->propertyPath = $propertyPath;
  }

  /**
   * {@inheritdoc}
   */
  public function setConstraint(Constraint $constraint): void {
    $this->constraint = $constraint;
  }

  /**
   * {@inheritdoc}
   */
  public function addViolation(string $message, array $params = []): void {
    $this->violations->add(new ConstraintViolation($this->translator->trans($message, $params, $this->translationDomain), $message, $params, $this->root, $this->propertyPath, $this->value, NULL, NULL, $this->constraint));
  }

  /**
   * {@inheritdoc}
   */
  public function buildViolation(string $message, array $parameters = []): ConstraintViolationBuilderInterface {
    return new ConstraintViolationBuilder($this->violations, $this->constraint, $message, $parameters, $this->root, $this->propertyPath, $this->value, $this->translator, $this->translationDomain);
  }

  /**
   * {@inheritdoc}
   */
  public function getViolations(): ConstraintViolationListInterface {
    return $this->violations;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidator(): ValidatorInterface {
    return $this->validator;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoot(): mixed {
    return $this->root;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(): mixed {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getObject(): ?object {
    return $this->object;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(): ?MetadataInterface {
    return $this->metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): ?string {
    return Constraint::DEFAULT_GROUP;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup(?string $group): void {
    $this->group = $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassName(): ?string {
    return get_class($this->object);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyName(): ?string {
    return $this->metadata instanceof PropertyMetadataInterface ? $this->metadata->getPropertyName() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyPath(string $subPath = ''): string {
    return PropertyPath::append($this->propertyPath, $subPath);
  }

  /**
   * {@inheritdoc}
   */
  public function markConstraintAsValidated(string $cacheKey, string $constraintHash): void {
    $this->validatedConstraints[$cacheKey . ':' . $constraintHash] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isConstraintValidated(string $cacheKey, string $constraintHash): bool {
    return isset($this->validatedConstraints[$cacheKey . ':' . $constraintHash]);
  }

  /**
   * {@inheritdoc}
   */
  public function markGroupAsValidated(string $cacheKey, string $groupHash): void {
    $this->validatedObjects[$cacheKey][$groupHash] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isGroupValidated(string $cacheKey, string $groupHash): bool {
    return isset($this->validatedObjects[$cacheKey][$groupHash]);
  }

  /**
   * {@inheritdoc}
   */
  public function markObjectAsInitialized(string $cacheKey): void {
    throw new \LogicException(ExecutionContextInterface::class . '::markObjectAsInitialized is unsupported.');
  }

  /**
   * {@inheritdoc}
   */
  public function isObjectInitialized(string $cacheKey): bool {
    throw new \LogicException(ExecutionContextInterface::class . '::isObjectInitialized is unsupported.');
  }

  /**
   * Clone this context.
   */
  public function __clone(): void {
    $this->violations = clone $this->violations;
  }

}
