<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UserNameUnique.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a user name is unique on the site.
 *
 * @Plugin(
 *   id = "UserNameUnique",
 *   label = @Translation("User name unique", context = "Validation"),
 * )
 */
class UserNameUnique extends Constraint {

  public $message = 'The username %value is already taken.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }
}
