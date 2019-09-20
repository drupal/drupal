<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\workspaces\WorkspaceAssociationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if data still exists for a deleted workspace ID.
 */
class DeletedWorkspaceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * Creates a new DeletedWorkspaceConstraintValidator instance.
   *
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   */
  public function __construct(WorkspaceAssociationInterface $workspace_association) {
    $this->workspaceAssociation = $workspace_association;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspaces.association')
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

    if ($this->workspaceAssociation->getTrackedEntities($value->getEntity()->id())) {
      $this->context->addViolation($constraint->message);
    }
  }

}
