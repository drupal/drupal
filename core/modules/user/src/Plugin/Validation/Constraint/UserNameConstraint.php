<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a value is a valid user name.
 */
#[Constraint(
  id: 'UserName',
  label: new TranslatableMarkup('User name', [], ['context' => 'Validation'])
)]
class UserNameConstraint extends SymfonyConstraint {

  public $emptyMessage = 'You must enter a username.';
  public $spaceBeginMessage = 'The username cannot begin with a space.';
  public $spaceEndMessage = 'The username cannot end with a space.';
  public $multipleSpacesMessage = 'The username cannot contain multiple spaces in a row.';
  public $illegalMessage = 'The username contains an illegal character.';
  public $tooLongMessage = 'The username %name is too long: it must be %max characters or less.';

}
