<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Checks if a user name is unique on the site.
 *
 * @Constraint(
 *   id = "UserNameUnique",
 *   label = @Translation("User name unique", context = "Validation"),
 * )
 */
class UserNameUnique extends UniqueFieldConstraint {

  public $message = 'The username %value is already taken.';

}
