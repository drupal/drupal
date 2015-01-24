<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\CommentNameConstraintValidator.
 */

namespace Drupal\comment\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\comment\CommentInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CommentName constraint.
 */
class CommentNameConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * User storage handler.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new CommentNameConstraintValidator.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage handler.
   */
  public function __construct(UserStorageInterface $user_storage) {
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager')->getStorage('user'));
  }

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
      $users = $this->userStorage->loadByProperties(array('name' => $author_name));
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
