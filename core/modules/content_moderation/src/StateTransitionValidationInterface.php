<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Validates whether a certain state transition is allowed.
 */
interface StateTransitionValidationInterface {

  /**
   * Gets a list of transitions that are legal for this user on this entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be transitioned.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The account that wants to perform a transition.
   *
   * @return \Drupal\workflows\Transition[]
   *   The list of transitions that are legal for this user on this entity.
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user);

  /**
   * Checks if a transition between two states if valid for the given user.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow entity.
   * @param \Drupal\workflows\StateInterface $original_state
   *   The original workflow state.
   * @param \Drupal\workflows\StateInterface $new_state
   *   The new workflow state.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to validate.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   (optional) The entity to be transitioned. Omitting this parameter is
   *   deprecated and will be required in Drupal 9.0.0.
   *
   * @return bool
   *   Returns TRUE if transition is valid, otherwise FALSE.
   */
  public function isTransitionValid(WorkflowInterface $workflow, StateInterface $original_state, StateInterface $new_state, AccountInterface $user, ContentEntityInterface $entity = NULL);

}
