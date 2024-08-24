<?php

declare(strict_types=1);

namespace Drupal\media_test_source\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * A media test constraint.
 */
#[Constraint(
  id: 'MediaTestConstraint',
  label: new TranslatableMarkup('Media constraint for test purposes.', [], ['context' => 'Validation']),
  type: ['entity', 'string']
)]
class MediaTestConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Inappropriate text.';

}
