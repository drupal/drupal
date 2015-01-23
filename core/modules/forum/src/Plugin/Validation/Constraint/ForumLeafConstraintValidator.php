<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\Validation\Constraint\ForumLeafConstraintValidator.
 */

namespace Drupal\forum\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ForumLeaf constraint.
 */
class ForumLeafConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!isset($items)) {
      return;
    }
    $item = $items->first();

    // Verify that a term has been selected.
    if (!$item->entity) {
      $this->context->addViolation($constraint->selectForum);
    }

    // The forum_container flag must not be set.
    if (!empty($item->entity->forum_container->value)) {
      $this->context->addViolation($constraint->noLeafMessage, array('%forum' => $item->entity->getName()));
    }
  }

}
