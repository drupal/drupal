<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Deleted workspace constraint.
 *
 * @Constraint(
 *   id = "DeletedWorkspace",
 *   label = @Translation("Deleted workspace", context = "Validation"),
 * )
 */
class DeletedWorkspaceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'A workspace with this ID has been deleted but data still exists for it.';

}
