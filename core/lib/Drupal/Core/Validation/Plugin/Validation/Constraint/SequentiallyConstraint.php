<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\CompositeConstraintInterface;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\SequentiallyValidator;

/**
 * Checks constraints sequentially and shows the error from the first.
 */
#[Constraint(
  id: 'Sequentially',
  label: new TranslatableMarkup('Sequentially validate multiple constraints', [], ['context' => 'Validation'])
)]
class SequentiallyConstraint extends Sequentially implements CompositeConstraintInterface {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeOptionStatic(): string {
    return 'constraints';
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return SequentiallyValidator::class;
  }

}
