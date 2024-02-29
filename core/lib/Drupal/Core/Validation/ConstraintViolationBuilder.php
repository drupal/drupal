<?php

namespace Drupal\Core\Validation;

// phpcs:ignoreFile Portions of this file are a direct copy of
// \Symfony\Component\Validator\Violation\ConstraintViolationBuilder.

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * A constraint violation builder for the basic Symfony validator.
 *
 * We do not use the builder provided by Symfony as it is marked internal.
 *
 */
class ConstraintViolationBuilder implements ConstraintViolationBuilderInterface {

  /**
   * The number used
   */
  protected ?int $plural = NULL;

  /**
   * The code.
   */
  protected mixed $code = NULL;

  /**
   * The cause.
   */
  protected mixed $cause = NULL;

  /**
   * Constructs a new ConstraintViolationBuilder instance.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationList $violations
   *   The violation list.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint.
   * @param string $message
   *   The message.
   * @param array $parameters
   *   The message parameters.
   * @param mixed $root
   *   The root.
   * @param string $propertyPath
   *   The property string.
   * @param mixed $invalidValue
   *   The invalid value.
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator.
   * @param string|false|null $translationDomain
   *   (optional) The translation domain.
   */
  public function __construct(
    protected ConstraintViolationList $violations,
    protected Constraint $constraint,
    protected string $message,
    protected array $parameters,
    protected mixed $root,
    protected string $propertyPath,
    protected mixed $invalidValue,
    protected TranslatorInterface $translator,
    protected string | false | null $translationDomain = NULL
  ) {}

  /**
   * {@inheritdoc}
   */
  public function atPath(mixed $path): static {
    if (!is_string($path)) {
      @\trigger_error('Passing the $path parameter as a non-string value to ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3396238', E_USER_DEPRECATED);
    }
    $this->propertyPath = PropertyPath::append($this->propertyPath, (string) $path);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter(string $key, mixed $value): static {
    $this->parameters[$key] = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParameters(array $parameters): static {
    $this->parameters = $parameters;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslationDomain(string $translationDomain): static {
    $this->translationDomain = $translationDomain;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setInvalidValue(mixed $invalidValue): static {
    $this->invalidValue = $invalidValue;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlural(int $number): static {
    $this->plural = $number;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode(?string $code): static {
    $this->code = $code;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCause(mixed $cause): static {
    $this->cause = $cause;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addViolation(): void {
    if (NULL === $this->plural) {
      $translatedMessage = $this->translator->trans(
        $this->message,
        $this->parameters,
        $this->translationDomain
      );
    }
    else {
      try {
        $translatedMessage = $this->translator->transChoice(
          $this->message,
          $this->plural,
          $this->parameters,
          $this->translationDomain#
        );
      }
      catch (\InvalidArgumentException $e) {
        $translatedMessage = $this->translator->trans(
          $this->message,
          $this->parameters,
          $this->translationDomain
        );
      }
    }

    $this->violations->add(new ConstraintViolation(
      $translatedMessage,
      $this->message,
      $this->parameters,
      $this->root,
      $this->propertyPath,
      $this->invalidValue,
      $this->plural,
      $this->code,
      $this->constraint,
      $this->cause
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function disableTranslation(): static {
    $this->translationDomain = FALSE;

    return $this;
  }

}
