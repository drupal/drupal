<?php

namespace Drupal\block_content\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * Validation constraint for the block content entity changed timestamp.
 */
#[Constraint(
  id: 'BlockContentEntityChanged',
  label: new TranslatableMarkup('Block content entity changed', [], ['context' => 'Validation']),
  type: ['entity']
)]
class BlockContentEntityChangedConstraint extends EntityChangedConstraint {
}
