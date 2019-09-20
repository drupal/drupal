<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\workspaces\WorkspaceAssociationStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if data still exists for a deleted workspace ID.
 */
class DeletedWorkspaceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The workspace association storage.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationStorageInterface
   */
  protected $workspaceAssociationStorage;

  /**
   * Creates a new DeletedWorkspaceConstraintValidator instance.
   *
   * @param \Drupal\workspaces\WorkspaceAssociationStorageInterface $workspace_association_storage
   *   The workspace association storage.
   */
  public function __construct(WorkspaceAssociationStorageInterface $workspace_association_storage) {
    $this->workspaceAssociationStorage = $workspace_association_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('workspace_association')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    // This constraint applies only to newly created workspace entities.
    if (!isset($value) || !$value->getEntity()->isNew()) {
      return;
    }

    $count = $this->workspaceAssociationStorage
      ->getQuery()
      ->allRevisions()
      ->accessCheck(FALSE)
      ->condition('workspace', $value->getEntity()->id())
      ->count()
      ->execute();
    if ($count) {
      $this->context->addViolation($constraint->message);
    }
  }

}
