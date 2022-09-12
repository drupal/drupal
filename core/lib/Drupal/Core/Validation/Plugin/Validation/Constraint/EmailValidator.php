<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator as SymfonyEmailValidator;

/**
 * Email constraint.
 *
 * Overrides the symfony validator to use the HTML 5 setting.
 *
 * @internal Exists only to override the constructor to avoid a deprecation
 *   in Symfony 6, and will be removed in drupal:11.0.0.
 */
final class EmailValidator extends SymfonyEmailValidator {

  /**
   * {@inheritdoc}
   */
  public function __construct(string $defaultMode = Email::VALIDATION_MODE_HTML5) {
    parent::__construct($defaultMode);
  }

}
