<?php

namespace Drupal\media_test_source\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * A media test constraint.
 *
 * @Constraint(
 *   id = "MediaTestConstraint",
 *   label = @Translation("Media constraint for test purposes.", context = "Validation"),
 *   type = { "entity", "string" }
 * )
 */
class MediaTestConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Inappropriate text.';

}
