<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Range;

/**
 * Range constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 *
 * @todo: Move this below the TypedData core component.
 *
 * @Constraint(
 *   id = "Range",
 *   label = @Translation("Range", context = "Validation"),
 *   type = { "integer", "float" }
 * )
 */
class RangeConstraint extends Range {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $options = NULL) {
    if (isset($options['min']) && isset($options['max'])) {
      $options['notInRangeMessage'] = $options['notInRangeMessage'] ?? 'This value should be between %min and %max.';
    }
    else {
      $options['minMessage'] = $options['minMessage'] ?? 'This value should be %limit or more.';
      $options['maxMessage'] = $options['maxMessage'] ?? 'This value should be %limit or less.';
    }
    parent::__construct($options);
  }

}
