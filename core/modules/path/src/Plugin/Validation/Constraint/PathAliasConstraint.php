<?php

namespace Drupal\path\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for changing path aliases in pending revisions.
 */
#[Constraint(
  id: 'PathAlias',
  label: new TranslatableMarkup('Path alias.', [], ['context' => 'Validation'])
)]
class PathAliasConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'You can only change the URL alias for the <em>published</em> version of this content.';

}
