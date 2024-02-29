<?php

namespace Drupal\Core\TypedData\Validation;

// phpcs:ignoreFile Portions of this file are a direct copy of
// \Symfony\Component\Validator\Violation\ConstraintViolationBuilder.

use Drupal\Core\Validation\ConstraintViolationBuilder as NewConstraintViolationBuilder;
use Drupal\Core\Validation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Defines a constraint violation builder for the Typed Data validator.
 *
 * We do not use the builder provided by Symfony as it is marked internal.
 *
 */
class ConstraintViolationBuilder extends NewConstraintViolationBuilder {

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
  public function __construct(ConstraintViolationList $violations, Constraint $constraint, $message, array $parameters, $root, $propertyPath, $invalidValue, TranslatorInterface $translator, $translationDomain = null)   {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Instead, use \Drupal\Core\Validation\ConstraintViolationBuilder. See https://www.drupal.org/node/3396238', E_USER_DEPRECATED);
    parent::__construct($violations, $constraint, $message, $parameters, $root, $propertyPath, $invalidValue, $translator, $translationDomain);
  }

}
