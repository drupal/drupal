<?php

namespace Drupal\path\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for changing path aliases in pending revisions.
 *
 * @Constraint(
 *   id = "PathAlias",
 *   label = @Translation("Path alias.", context = "Validation"),
 * )
 */
class PathAliasConstraint extends Constraint {

  public $message = 'You can only change the URL alias for the <em>published</em> version of this content.';

}
