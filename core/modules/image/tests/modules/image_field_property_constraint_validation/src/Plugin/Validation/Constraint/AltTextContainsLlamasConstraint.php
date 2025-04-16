<?php

declare(strict_types=1);

namespace Drupal\image_field_property_constraint_validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Provides a Contains Llamas constraint.
 */
#[Constraint(
  id: 'AltTextContainsLlamas',
  label: new TranslatableMarkup('Contains Llamas', options: ['context' => 'Validation'])
)]
final class AltTextContainsLlamasConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'Alternative text must contain some llamas.';

}
