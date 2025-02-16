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

  /**
   * The violation message when there is no username.
   *
   * @var string
   */
  public $emptyMessage = 'You must enter a username.';

  /**
   * The violation message when the username begins with whitespace.
   *
   * @var string
   */
  public $spaceBeginMessage = 'The username cannot begin with a space.';

  /**
   * The violation message when the username ends with whitespace.
   *
   * @var string
   */
  public $spaceEndMessage = 'The username cannot end with a space.';

  /**
   * The violation message when the username has consecutive whitespace.
   *
   * @var string
   */
  public $multipleSpacesMessage = 'The username cannot contain multiple spaces in a row.';

  /**
   * The violation message when the username uses an invalid character.
   *
   * @var string
   */
  public $illegalMessage = 'The username contains an illegal character.';

  /**
   * The violation message when the username length exceeds the maximum allowed.
   *
   * @var string
   */
  public $tooLongMessage = 'The username %name is too long: it must be %max characters or less.';

}
