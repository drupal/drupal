<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a string conforms to the RFC 3986 host component.
 */
#[Constraint(
  id: 'UriHost',
  label: new TranslatableMarkup('URI host', [], ['context' => 'Validation']),
)]
class UriHostConstraint extends SymfonyConstraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public string $message = 'This value should conform to RFC 3986 URI host component.';

}
