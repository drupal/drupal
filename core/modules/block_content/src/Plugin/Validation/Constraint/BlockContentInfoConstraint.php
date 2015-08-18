<?php

/**
 * @file
 * Contains \Drupal\block_content\Plugin\Validation\Constraint\BlockContentInfoConstraint.
 */

namespace Drupal\block_content\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating custom block names.
 *
 * @Constraint(
 *   id = "BlockContentInfo",
 *   label = @Translation("Custom block name", context = "Validation")
 * )
 */
class BlockContentInfoConstraint extends Constraint {

  public $message = 'A block with description %value already exists.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }

}
