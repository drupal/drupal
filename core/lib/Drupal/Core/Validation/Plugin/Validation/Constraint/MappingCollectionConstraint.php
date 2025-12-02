<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\CompositeConstraintInterface;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Checks that at least one of the given constraints is satisfied.
 *
 * Overrides the Symfony constraint to convert the array of constraint IDs to an
 * array of constraint objects and use them.
 */
#[Constraint(
  id: 'MappingCollection',
  label: new TranslatableMarkup('Validate mapping as a Collection', [], ['context' => 'Validation'])
)]
class MappingCollectionConstraint extends Collection implements CompositeConstraintInterface {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeOptionStatic(): string {
    return 'fields';
  }

}
