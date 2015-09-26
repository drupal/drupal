<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UserMailUnique.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Checks if a user's email address is unique on the site.
 *
 * @Constraint(
 *   id = "UserMailUnique",
 *   label = @Translation("User email unique", context = "Validation")
 * )
 */
class UserMailUnique extends UniqueFieldConstraint {

  public $message = 'The email address %value is already taken.';

}
