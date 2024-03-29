<?php

namespace Drupal\block_content\Plugin\Validation\Constraint;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the BlockContentEntityChanged constraint.
 */
class BlockContentEntityChangedConstraintValidator extends EntityChangedConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    // This prevents saving an update to the block via a host entity's form if
    // the host entity has had other changes made via the API instead of the
    // entity form, such as a revision revert. This is safe, for example, in the
    // Layout Builder the inline blocks are not saved until the whole layout is
    // saved, in which case Layout Builder forces a new revision for the block.
    // @see \Drupal\layout_builder\InlineBlockEntityOperations::handlePreSave.
    if ($entity instanceof BlockContentInterface && !$entity->isReusable()) {
      return;
    }
    parent::validate($entity, $constraint);
  }

}
