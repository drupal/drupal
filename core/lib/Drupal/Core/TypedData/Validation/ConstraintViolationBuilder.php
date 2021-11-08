<?php

namespace Drupal\Core\TypedData\Validation;

// phpcs:ignoreFile Portions of this file are a direct copy of
// \Symfony\Component\Validator\Violation\ConstraintViolationBuilder.

use Drupal\Core\Validation\TranslatorInterface;
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
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class
 *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder instead.
 *
 * @see https://www.drupal.org/node/3238432
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
   * @var \Drupal\Core\Validation\TranslatorInterface
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
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator.
   * @param null $translationDomain
   *   (optional) The translation domain.
   */
  public function __construct(ConstraintViolationList $violations, Constraint $constraint, $message, array $parameters, $root, $propertyPath, $invalidValue, TranslatorInterface $translator, $translationDomain = null)
    {
      @trigger_error(__CLASS__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class \Symfony\Component\Validator\Violation\ConstraintViolationBuilder instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
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
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::atPath()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function atPath($path)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::atPath() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->propertyPath = PropertyPath::append($this->propertyPath, $path);

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setParameter()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function setParameter($key, $value)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setParameter() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->parameters[$key] = $value;

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setParameters()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function setParameters(array $parameters)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setParameters() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->parameters = $parameters;

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setTranslationDomain()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function setTranslationDomain($translationDomain)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setTranslationDomain() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->translationDomain = $translationDomain;

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setInvalidValue()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function setInvalidValue($invalidValue)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setInvalidValue() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->invalidValue = $invalidValue;

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setPlural()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function setPlural($number)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setPlural() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->plural = $number;

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setCode()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function setCode($code)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setCode() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->code = $code;

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setCause()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function setCause($cause)
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::setCause() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
      $this->cause = $cause;

      return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
     *   \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::addViolation()
     *   instead.
     *
     * @see https://www.drupal.org/node/3238432
     */
    public function addViolation()
    {
      @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Validator\Violation\ConstraintViolationBuilder::addViolation() instead. See https://www.drupal.org/node/3238432', E_USER_DEPRECATED);
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
