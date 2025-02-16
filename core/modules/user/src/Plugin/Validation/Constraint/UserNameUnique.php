<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Checks if a user name is unique on the site.
 */
#[Constraint(
  id: 'UserNameUnique',
  label: new TranslatableMarkup('User name unique', [], ['context' => 'Validation'])
)]
class UserNameUnique extends UniqueFieldConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The username %value is already taken.';

}
