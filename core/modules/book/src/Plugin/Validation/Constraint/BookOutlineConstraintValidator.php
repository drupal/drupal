<?php

namespace Drupal\book\Plugin\Validation\Constraint;

use Drupal\book\BookManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing the book outline in pending revisions.
 */
class BookOutlineConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Creates a new BookOutlineConstraintValidator instance.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   */
  public function __construct(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity) && !$entity->isNew() && !$entity->isDefaultRevision()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
      $original = $this->bookManager->loadBookLink($entity->id(), FALSE) ?: [
        'bid' => 0,
        'weight' => 0,
      ];
      if (empty($original['pid'])) {
        $original['pid'] = -1;
      }

      if ($entity->book['bid'] != $original['bid']) {
        $this->context->buildViolation($constraint->message)
          ->atPath('book.bid')
          ->setInvalidValue($entity)
          ->addViolation();
      }
      if ($entity->book['pid'] != $original['pid']) {
        $this->context->buildViolation($constraint->message)
          ->atPath('book.pid')
          ->setInvalidValue($entity)
          ->addViolation();
      }
      if ($entity->book['weight'] != $original['weight']) {
        $this->context->buildViolation($constraint->message)
          ->atPath('book.weight')
          ->setInvalidValue($entity)
          ->addViolation();
      }
    }
  }

}
