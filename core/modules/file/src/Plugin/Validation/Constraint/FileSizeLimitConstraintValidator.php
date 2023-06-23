<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the FileSizeLimitConstraint.
 */
class FileSizeLimitConstraintValidator extends BaseFileConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new FileSizeConstraintValidator.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileSizeLimitConstraint) {
      throw new UnexpectedTypeException($constraint, FileSizeLimitConstraint::class);
    }

    $fileLimit = $constraint->fileLimit;

    if ($fileLimit && $file->getSize() > $fileLimit) {
      $this->context->addViolation($constraint->maxFileSizeMessage, [
        '%filesize' => format_size($file->getSize()),
        '%maxsize' => format_size($fileLimit),
      ]);
    }

    $userLimit = $constraint->userLimit;

    // Save a query by only calling spaceUsed() when a limit is provided.
    if ($userLimit) {
      /** @var \Drupal\file\FileStorageInterface $fileStorage */
      $fileStorage = $this->entityTypeManager->getStorage('file');
      $spaceUsed = $fileStorage->spaceUsed($this->currentUser->id()) + $file->getSize();
      if ($spaceUsed > $userLimit) {
        $this->context->addViolation($constraint->diskQuotaMessage, [
          '%filesize' => format_size($file->getSize()),
          '%quota' => format_size($userLimit),
        ]);
      }
    }
  }

}
