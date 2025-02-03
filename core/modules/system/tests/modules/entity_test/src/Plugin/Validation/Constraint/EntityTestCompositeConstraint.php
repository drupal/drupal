<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * Constraint with multiple fields.
 */
#[Constraint(
  id: 'EntityTestComposite',
  label: new TranslatableMarkup('Constraint with multiple fields.'),
  type: ['entity']
)]
class EntityTestCompositeConstraint extends CompositeConstraintBase {

  /**
   * The default violation message.
   */
  public string $message = 'Multiple fields are validated';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['name', 'type'];
  }

}
