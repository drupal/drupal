<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\Transition;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation implements StateTransitionValidationInterface {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Stores the possible state transitions.
   *
   * @var array
   */
  protected $possibleTransitions = [];

  /**
   * Constructs a new StateTransitionValidation.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_info) {
    $this->moderationInfo = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    $current_state = $entity->moderation_state->value ? $workflow->getTypePlugin()->getState($entity->moderation_state->value) : $workflow->getTypePlugin()->getInitialState($entity);

    return array_filter($current_state->getTransitions(), function (Transition $transition) use ($workflow, $user) {
      return $user->hasPermission('use ' . $workflow->id() . ' transition ' . $transition->id());
    });
  }

}
