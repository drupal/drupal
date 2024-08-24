<?php

declare(strict_types=1);

namespace Drupal\field_test\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Checks if a value is not equal.
 */
#[Constraint(
  id: 'TestField',
  label: new TranslatableMarkup('Test Field', [], ['context' => 'Validation']),
  type: ['integer']
)]
class TestFieldConstraint extends NotEqualTo {

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return '\Symfony\Component\Validator\Constraints\NotEqualToValidator';
  }

}
