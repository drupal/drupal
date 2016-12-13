<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

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

}
