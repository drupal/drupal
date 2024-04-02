<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspaces\WorkspaceAssociationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityWorkspaceConflict constraint.
 *
 * @internal
 */
class EntityWorkspaceConflictConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly WorkspaceManagerInterface $workspaceManager,
    protected readonly WorkspaceAssociationInterface $workspaceAssociation,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('workspaces.manager'),
      $container->get('workspaces.association'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if (isset($entity) && !$entity->isNew()) {
      $active_workspace = $this->workspaceManager->getActiveWorkspace();

      // If the entity is tracked in a workspace, it can only be edited in
      // that workspace or one of its descendants.
      if ($tracking_workspace_ids = $this->workspaceAssociation->getEntityTrackingWorkspaceIds($entity, TRUE)) {
        if (!$active_workspace || !in_array($active_workspace->id(), $tracking_workspace_ids, TRUE)) {
          $first_tracking_workspace_id = reset($tracking_workspace_ids);
          $workspace = $this->entityTypeManager->getStorage('workspace')
            ->load($first_tracking_workspace_id);

          $this->context->buildViolation($constraint->message)
            ->setParameter('@label', $workspace->label())
            ->addViolation();
        }
      }
    }
  }

}
