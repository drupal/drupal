<?php

namespace Drupal\Core\Field;

use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Wraps a violation to allow arrayPropertyPath to be deprecated.
 *
 * @internal
 *   A BC shim for PHP 8.2.
 */
final class InternalViolation implements ConstraintViolationInterface {

  /**
   * The array property path.
   *
   * @var array
   */
  private $arrayPropertyPath;

  /**
   * The violation being wrapped.
   *
   * @var \Symfony\Component\Validator\ConstraintViolationInterface
   */
  private $violation;

  /**
   * An array of dynamic properties.
   *
   * @var array
   */
  private $properties = [];

  /**
   * Constructs a InternalViolation object.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $violation
   *   The violation to wrap.
   */
  public function __construct(ConstraintViolationInterface $violation) {
    $this->violation = $violation;
  }

  /**
   * {@inheritdoc}
   */
  public function __get(string $name) {
    if ($name === 'arrayPropertyPath') {
      @trigger_error('Accessing the arrayPropertyPath property is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. Use \Symfony\Component\Validator\ConstraintViolationInterface::getPropertyPath() instead. See https://www.drupal.org/node/3307919', E_USER_DEPRECATED);
      return $this->arrayPropertyPath;
    }
    @trigger_error('Accessing dynamic properties on violations is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3307919', E_USER_DEPRECATED);
    return $this->properties[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function __set(string $name, $value): void {
    if ($name === 'arrayPropertyPath') {
      $this->arrayPropertyPath = $value;
      return;
    }
    @trigger_error('Setting dynamic properties on violations is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3307919', E_USER_DEPRECATED);
    $this->properties[$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return (string) $this->violation;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->violation->getMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageTemplate(): string {
    return $this->violation->getMessageTemplate();
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters(): array {
    return $this->violation->getParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlural(): ?int {
    return $this->violation->getPlural();
  }

  /**
   * {@inheritdoc}
   */
  public function getRoot() {
    return $this->violation->getRoot();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyPath(): string {
    return $this->violation->getPropertyPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getInvalidValue() {
    return $this->violation->getInvalidValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getCode(): ?string {
    return $this->violation->getCode();
  }

}
