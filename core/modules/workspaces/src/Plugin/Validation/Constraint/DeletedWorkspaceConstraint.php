<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Deleted workspace constraint.
 */
#[Constraint(
  id: 'DeletedWorkspace',
  label: new TranslatableMarkup('Deleted workspace', [], ['context' => 'Validation'])
)]
class DeletedWorkspaceConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'A workspace with this ID has been deleted but data still exists for it.';

}
