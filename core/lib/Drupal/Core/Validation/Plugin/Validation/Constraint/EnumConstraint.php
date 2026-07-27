<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that a value is a valid Enum.
 */
#[Constraint(
  id: 'Enum',
  label: new TranslatableMarkup('Enum', [], ['context' => 'Validation']),
)]
class EnumConstraint extends SymfonyConstraint {

  /**
   * Violation message if the value is not an enum.
   */
  public string $notEnumMessage = 'The value you selected is not a valid enum.';

  /**
   * Violation message if the enum class is missing.
   */
  public string $missingClassMessage = 'The Enum data definition must supply an "enum_class".';

  /**
   * Violation message if the value is not of the expected enum class.
   */
  public string $invalidClassMessage = 'The value must be an instance of %class.';

}
