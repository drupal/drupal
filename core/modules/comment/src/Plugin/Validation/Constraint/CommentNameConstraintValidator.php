<?php

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
    return new static($container->get('entity_type.manager')->getStorage('user'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    $author_name = $entity->name->value;
    $owner_id = (int) $entity->uid->target_id;

    // Do not allow unauthenticated comment authors to use a name that is
    // taken by a registered user.
    if (isset($author_name) && $author_name !== '' && $owner_id === 0) {
      $users = $this->userStorage->loadByProperties(['name' => $author_name]);
      if (!empty($users)) {
        $this->context->buildViolation($constraint->messageNameTaken, ['%name' => $author_name])
          ->atPath('name')
          ->addViolation();
      }
    }
    // If an author name and owner are given, make sure they match.
    elseif (isset($author_name) && $author_name !== '' && $owner_id) {
      $owner = $this->userStorage->load($owner_id);
      if ($owner->getAccountName() != $author_name) {
        $this->context->buildViolation($constraint->messageMatch)
          ->atPath('name')
          ->addViolation();
      }
    }

    // Anonymous account might be required - depending on field settings. We
    // can't validate this without a valid commented entity, which will fail
    // the validation elsewhere.
    if ($owner_id === 0 && empty($author_name) && $entity->getCommentedEntity() && $entity->getFieldName() &&
      $this->getAnonymousContactDetailsSetting($entity) === CommentInterface::ANONYMOUS_MUST_CONTACT) {
      $this->context->buildViolation($constraint->messageRequired)
        ->atPath('name')
        ->addViolation();
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
