<?php

namespace Drupal\Component\Utility;

use Egulias\EmailValidator\EmailValidator as EmailValidatorUtility;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * Validates email addresses.
 */
class EmailValidator extends EmailValidatorUtility implements EmailValidatorInterface {

  /**
   * Validates an email address.
   *
   * @param string $email
   *   A string containing an email address.
   * @param \Egulias\EmailValidator\Validation\EmailValidation|null $email_validation
   *   This argument is ignored. If it is supplied an error will be triggered.
   *   See https://www.drupal.org/node/2997196.
   *
   * @return bool
   *   TRUE if the address is valid.
   */
  public function isValid($email, ?EmailValidation $email_validation = NULL) {
    if ($email_validation) {
      throw new \BadMethodCallException('Calling \Drupal\Component\Utility\EmailValidator::isValid() with the second argument is not supported. See https://www.drupal.org/node/2997196');
    }
    return parent::isValid($email, (new RFCValidation()));
  }

}
