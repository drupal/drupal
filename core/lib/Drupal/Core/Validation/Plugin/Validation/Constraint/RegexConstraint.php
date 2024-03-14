<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Regex constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 */
#[Constraint(
  id: 'Regex',
  label: new TranslatableMarkup('Regex', [], ['context' => 'Validation'])
)]
class RegexConstraint extends Regex {

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The name of the class that validates this constraint.
   *
   * @todo Add method return type declaration.
   * @see https://www.drupal.org/project/drupal/issues/3425150
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\RegexValidator';
  }

}
