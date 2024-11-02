<?php

namespace Drupal\workflows;

/**
 * An interface for state value objects.
 *
 * @internal
 *   The StateInterface should only be used by Workflows and Content Moderation.
 * @todo Revisit the need for this in https://www.drupal.org/node/2902309.
 */
interface StateInterface {

  /**
   * The key of the state plugin form.
   */
  const PLUGIN_FORM_KEY = 'state';

  /**
   * Gets the state's ID.
   *
   * @return string
   *   The state's ID.
   */
  public function id();

  /**
   * Gets the state's label.
   *
   * @return string
   *   The state's label.
   */
  public function label();

  /**
   * Gets the state's weight.
   *
   * @return int
   *   The state's weight.
   */
  public function weight();

  /**
   * Determines if the state can transition to the provided state ID.
   *
   * @param $to_state_id
   *   The state to transition to.
   *
   * @return bool
   *   TRUE if the state can transition to the provided state ID. FALSE, if not.
   */
  public function canTransitionTo($to_state_id);

  /**
   * Gets the Transition object for the provided state ID.
   *
   * @param $to_state_id
   *   The state to transition to.
   *
   * @return \Drupal\workflows\TransitionInterface
   *   The Transition object for the provided state ID.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the provided state ID can not be transitioned to.
   */
  public function getTransitionTo($to_state_id);

  /**
   * Gets all the possible transition objects for the state.
   *
   * @return \Drupal\workflows\TransitionInterface[]
   *   All the possible transition objects for the state.
   */
  public function getTransitions();

}
