<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\CompositeConstraintInterface;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\Required;

/**
 * Marks a field as required in a Collection constraint.
 */
#[Constraint(
  id: 'Required',
  label: new TranslatableMarkup('Mark a field as required in a Collection constraint', [], ['context' => 'Validation'])
)]
class RequiredConstraint extends Required implements CompositeConstraintInterface {

  #[HasNamedArguments]
  public function __construct(...$args) {
    parent::__construct(...$args);
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeOptionStatic(): array|string {
    return 'constraints';
  }

}
