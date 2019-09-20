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
 */
class EntityWorkspaceConflictConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workspace manager service.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * Constructs an EntityUntranslatableFieldsConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager, WorkspaceAssociationInterface $workspace_association) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
    $this->workspaceAssociation = $workspace_association;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('workspaces.manager'),
      $container->get('workspaces.association')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if (isset($entity) && !$entity->isNew()) {
      $workspace_ids = $this->workspaceAssociation->getEntityTrackingWorkspaceIds($entity);
      $active_workspace = $this->workspaceManager->getActiveWorkspace();

      if ($workspace_ids && (!$active_workspace || !in_array($active_workspace->id(), $workspace_ids, TRUE))) {
        // An entity can only be edited in one workspace.
        $workspace_id = reset($workspace_ids);
        $workspace = $this->entityTypeManager->getStorage('workspace')->load($workspace_id);

        $this->context->buildViolation($constraint->message)
          ->setParameter('%label', $workspace->label())
          ->addViolation();
      }
    }
  }

}
