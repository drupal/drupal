<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if data still exists for a deleted workspace ID.
 */
class DeletedWorkspaceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Creates a new DeletedWorkspaceConstraintValidator instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
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

    $deleted_workspace_ids = $this->state->get('workspace.deleted', []);
    if (isset($deleted_workspace_ids[$value->getEntity()->id()])) {
      $this->context->addViolation($constraint->message);
    }
  }

}
