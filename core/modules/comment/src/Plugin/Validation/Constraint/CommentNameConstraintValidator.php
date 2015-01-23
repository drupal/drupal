<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\CommentNameConstraintValidator.
 */

namespace Drupal\comment\Plugin\Validation\Constraint;

use Drupal\comment\CommentInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CommentName constraint.
 */
class CommentNameConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var CommentNameConstraint $constraint */
    if (!isset($items)) {
      return;
    }
    /** @var CommentInterface $comment */
    $comment = $items->getEntity();

    if (!isset($comment)) {
      // Looks like we are validating a field not being part of a comment,
      // nothing we can do then.
      return;
    }
    $author_name = $items->first()->value;

    // Do not allow unauthenticated comment authors to use a name that is
    // taken by a registered user.
    if (isset($author_name) && $author_name !== '' && $comment->getOwnerId() === 0) {
      // @todo Properly inject dependency https://drupal.org/node/2197029
      $users = \Drupal::entityManager()->getStorage('user')->loadByProperties(array('name' => $author_name));
      if (!empty($users)) {
        $this->context->addViolation($constraint->messageNameTaken, array('%name' => $author_name));
      }
    }
    // If an author name and owner are given, make sure they match.
    elseif (isset($author_name) && $author_name !== '' && $comment->getOwnerId()) {
      $owner = $comment->getOwner();
      if ($owner->getUsername() != $author_name) {
        $this->context->addViolation($constraint->messageMatch);
      }
    }

    // Anonymous account might be required - depending on field settings.
    if ($comment->getOwnerId() === 0 && empty($author_name) &&
      $this->getAnonymousContactDetailsSetting($comment) === COMMENT_ANONYMOUS_MUST_CONTACT) {
      $this->context->addViolation($constraint->messageRequired);
    }
  }

  /**
   * Gets the anonymous contact details setting from the comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The entity.
   *
   * @return int
   *   The anonymous contact setting.
   */
  protected function getAnonymousContactDetailsSetting(CommentInterface $comment) {
    return $comment
      ->getCommentedEntity()
      ->get($comment->getFieldName())
      ->getFieldDefinition()
      ->getSetting('anonymous');
  }

}
