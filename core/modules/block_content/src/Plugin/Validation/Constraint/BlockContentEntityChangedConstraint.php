<?php

namespace Drupal\block_content\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraint;

/**
 * Validation constraint for the block content entity changed timestamp.
 *
 * @Constraint(
 *   id = "BlockContentEntityChanged",
 *   label = @Translation("Block content entity changed", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class BlockContentEntityChangedConstraint extends EntityChangedConstraint {
}
