<?php

namespace Drupal\content_moderation\Plugin\Validation\Constraint;

use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if a moderation state transition is valid.
 */
class ModerationStateConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new ModerationStateConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   The moderation information.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $stateTransitionValidation
   *   The state transition validation service.
   */
  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    protected ModerationInformationInterface $moderationInformation,
    protected AccountInterface $currentUser,
    protected StateTransitionValidationInterface $stateTransitionValidation,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('current_user'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $value->getEntity();

    // Ignore entities that are not subject to moderation anyway.
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    // If the entity is moderated and the item list is empty, ensure users see
    // the same required message as typical NotNull constraints.
    if ($value->isEmpty()) {
      $this->context->addViolation((new NotNullConstraint())->message);
      return;
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);

    if (!$workflow->getTypePlugin()->hasState($entity->moderation_state->value)) {
      // If the state we are transitioning to doesn't exist, we can't validate
      // the transitions for this entity further.
      $this->context->addViolation($constraint->invalidStateMessage, [
        '%state' => $entity->moderation_state->value,
        '%workflow' => $workflow->label(),
      ]);
      return;
    }

    $new_state = $workflow->getTypePlugin()->getState($entity->moderation_state->value);
    $original_state = $this->moderationInformation->getOriginalState($entity);

    // If a new state is being set and there is an existing state, validate
    // there is a valid transition between them.
    if (!$original_state->canTransitionTo($new_state->id())) {
      $this->context->addViolation($constraint->message, [
        '%from' => $original_state->label(),
        '%to' => $new_state->label(),
      ]);
    }
    else {
      // If we're sure the transition exists, make sure the user has permission
      // to use it.
      if (!$this->stateTransitionValidation->isTransitionValid($workflow, $original_state, $new_state, $this->currentUser, $entity)) {
        $this->context->addViolation($constraint->invalidTransitionAccess, [
          '%original_state' => $original_state->label(),
          '%new_state' => $new_state->label(),
        ]);
      }
    }
  }

}
