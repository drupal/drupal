<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Uniquely labeled list item constraint.
 *
 * @Constraint(
 *   id = "UniqueLabelInList",
 *   label = @Translation("Unique label in list", context = "Validation"),
 * )
 *
 * @internal
 */
class UniqueLabelInListConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The label %label is not unique.';

  /**
   * The key of the label that this validation constraint should check.
   *
   * @var null|string
   */
  public $labelKey = NULL;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['labelKey'];
  }

}
