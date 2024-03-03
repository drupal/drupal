<?php

namespace Drupal\Core\Path\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for valid system paths.
 */
#[Constraint(
  id: 'ValidPath',
  label: new TranslatableMarkup('Valid path.', [], ['context' => 'Validation'])
)]
class ValidPathConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = "Either the path '%link_path' is invalid or you do not have access to it.";

}
