<?php

declare(strict_types=1);

namespace Drupal\rest_test\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Adds some validations for a REST test field.
 *
 * @see \Drupal\Core\TypedData\OptionsProviderInterface
 */
#[Constraint(
  id: 'rest_test_validation',
  label: new TranslatableMarkup('REST test validation', [], ['context' => 'Validation'])
)]
class RestTestConstraint extends SymfonyConstraint {

  public $message = 'REST test validation failed';

}
