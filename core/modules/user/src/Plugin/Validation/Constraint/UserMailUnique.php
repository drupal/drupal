<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UserMailUnique.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a user's email address is unique on the site.
 *
 * @Plugin(
 *   id = "UserMailUnique",
 *   label = @Translation("User email unique", context = "Validation")
 * )
 */
class UserMailUnique extends Constraint {

  public $message = 'The email address %value is already taken.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }
}
