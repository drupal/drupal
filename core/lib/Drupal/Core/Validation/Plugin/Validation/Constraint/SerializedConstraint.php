<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks for valid serialized data.
 */
#[Constraint(
  id: 'Serialized',
  label: new TranslatableMarkup('Serialized', [], ['context' => 'Validation'])
)]
class SerializedConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public string $message = 'This value should be a serialized object.';

  /**
   * The violation message when the value is not a string.
   *
   * @var string
   */
  public string $wrongTypeMessage = 'This value should be a string, "{type}" given.';

}
