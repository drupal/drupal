<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UserMailUnique.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a user's e-mail address is unique on the site.
 *
 * @Plugin(
 *   id = "UserMailUnique",
 *   label = @Translation("User e-mail unique", context = "Validation")
 * )
 */
class UserMailUnique extends Constraint {

  public $message = 'The e-mail address %value is already taken.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\user\Plugin\Validation\Constraint\UserUniqueValidator';
  }
}
