<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Validation\ConstraintViolationBuilder.
 */

namespace Drupal\Core\TypedData\Validation;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Defines a constraint violation builder for the Typed Data validator.
 *
 * We do not use the builder provided by Symfony as it is marked internal.
 *
 * @codingStandardsIgnoreStart
 */
class ConstraintViolationBuilder implements ConstraintViolationBuilderInterface {

  /**
   * The list of violations.
   *
   * @var \Symfony\Component\Validator\ConstraintViolationList
   */
  protected $violations;

  /**
   * The violation message.
   *
   * @var string
   */
  protected $message;

  /**
   * The message parameters.
   *
   * @var array
   */
  protected $parameters;

  /**
   * The root path.
   *
   * @var mixed
   */
  protected $root;

  /**
   * The invalid value caused the violation.
   *
   * @var mixed
   */
  protected $invalidValue;

  /**
   * The property path.
   *
   * @var string
   */
  protected $propertyPath;

  /**
   * The translator.
   *
   * @var \Symfony\Component\Translation\TranslatorInterface
   */
  protected $translator;

  /**
   * The translation domain.
   *
   * @var string|null
   */
  protected $translationDomain;

  /**
   * The number used
   * @var int|null
   */
  protected $plural;

  /**
   * @var Constraint
   */
  protected $constraint;

  /**
   * @var mixed
   */
  protected $code;

  /**
   * @var mixed
   */
  protected $cause;

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
   * @param \Symfony\Component\Translation\TranslatorInterface $translator
   *   The translator.
   * @param null $translationDomain
   *   (optional) The translation domain.
   */
  public function __construct(ConstraintViolationList $violations, Constraint $constraint, $message, array $parameters, $root, $propertyPath, $invalidValue, TranslatorInterface $translator, $translationDomain = null)
    {
      $this->violations = $violations;
      $this->message = $message;
      $this->parameters = $parameters;
      $this->root = $root;
      $this->propertyPath = $propertyPath;
      $this->invalidValue = $invalidValue;
      $this->translator = $translator;
      $this->translationDomain = $translationDomain;
      $this->constraint = $constraint;
    }

    /**
     * {@inheritdoc}
     */
    public function atPath($path)
    {
      $this->propertyPath = PropertyPath::append($this->propertyPath, $path);

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($key, $value)
    {
      $this->parameters[$key] = $value;

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters)
    {
      $this->parameters = $parameters;

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setTranslationDomain($translationDomain)
    {
      $this->translationDomain = $translationDomain;

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setInvalidValue($invalidValue)
    {
      $this->invalidValue = $invalidValue;

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlural($number)
    {
      $this->plural = $number;

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCode($code)
    {
      $this->code = $code;

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCause($cause)
    {
      $this->cause = $cause;

      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addViolation()
    {
      if (null === $this->plural) {
        $translatedMessage = $this->translator->trans(
          $this->message,
          $this->parameters,
          $this->translationDomain
        );
      } else {
        try {
          $translatedMessage = $this->translator->transChoice(
            $this->message,
            $this->plural,
            $this->parameters,
            $this->translationDomain#
          );
        } catch (\InvalidArgumentException $e) {
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
}
