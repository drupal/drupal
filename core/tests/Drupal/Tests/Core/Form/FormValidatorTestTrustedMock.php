<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Stub class to implement TrustedCallbackInterface.
 */
class FormValidatorTestTrustedMock implements TrustedCallbackInterface {

  /**
   * Implements #validate callback for the FormValidatorTest class.
   */
  public static function validateHandler(array &$form, FormStateInterface &$form_state) {
    if (isset($form['#form_validator_test_validate_handler'])) {
      throw new ExpectationFailedException('\Drupal\Tests\Core\Form\FormValidatorTestTrustedMock::validateHandler called more than once.');
    }
    $form['#form_validator_test_validate_handler'] = TRUE;
  }

  /**
   * Implements #validate callback for the FormValidatorTest class.
   */
  public static function hashValidate(array &$form, FormStateInterface &$form_state) {
    if (isset($form['#form_validator_test_validate_hash'])) {
      throw new ExpectationFailedException('\Drupal\Tests\Core\Form\FormValidatorTestTrustedMock::validateHandler called more than once.');
    }
    $form['#form_validator_test_validate_hash'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['validateHandler', 'hashValidate'];
  }

}
