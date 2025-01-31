<?php

namespace Drupal\workflows;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * An interface for Workflow type plugins.
 */
interface WorkflowTypeInterface extends PluginWithFormsInterface, DerivativeInspectionInterface, ConfigurableInterface, DependentPluginInterface {

  /**
   * The key of the global workflow plugin form.
   */
  const PLUGIN_FORM_KEY = 'configure';

  /**
   * Gets the label for the workflow type.
   *
   * @return string
   *   The workflow type label.
   */
  public function label();

  /**
   * Determines if the workflow is being has data associated with it.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow to check.
   *
   * @return bool
   *   TRUE if the workflow is being used, FALSE if not.
   *
   * @internal
   *    Marked as internal until it's validated this should form part of the
   *    public API in https://www.drupal.org/node/2897148.
   */
  public function workflowHasData(WorkflowInterface $workflow);

  /**
   * Determines if the workflow state has data associated with it.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow to check.
   * @param \Drupal\workflows\StateInterface $state
   *   The workflow state to check.
   *
   * @return bool
   *   TRUE if the workflow state is being used, FALSE if not.
   *
   * @internal
   *   Marked as internal until it's validated this should form part of the
   *   public API in https://www.drupal.org/node/2897148.
   */
  public function workflowStateHasData(WorkflowInterface $workflow, StateInterface $state);

  /**
   * Gets the initial state for the workflow.
   *
   * @return \Drupal\workflows\StateInterface
   *   The initial state.
   */
  public function getInitialState();

  /**
   * Gets the required states of workflow type.
   *
   * This is usually specified in the workflow type annotation.
   *
   * @return string[]
   *   The required states.
   *
   * @see \Drupal\workflows\Annotation\WorkflowType
   */
  public function getRequiredStates();

  /**
   * Informs the plugin that a dependency of the workflow will be deleted.
   *
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *
   * @return bool
   *   TRUE if the workflow settings have been changed, FALSE if not.
   *
   * @see \Drupal\Core\Config\ConfigEntityInterface::onDependencyRemoval()
   *
   * @todo https://www.drupal.org/node/2579743 make part of a generic interface.
   */
  public function onDependencyRemoval(array $dependencies);

  /**
   * Adds a state to the workflow.
   *
   * @param string $state_id
   *   The state's ID.
   * @param string $label
   *   The state's label.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if a state already exists or state ID is invalid.
   */
  public function addState($state_id, $label);

  /**
   * Determines if the workflow has a state with the provided ID.
   *
   * @param string $state_id
   *   The state's ID.
   *
   * @return bool
   *   TRUE if the workflow has a state with the provided ID, FALSE if not.
   */
  public function hasState($state_id);

  /**
   * Gets state objects for the provided state IDs.
   *
   * @param string[] $state_ids
   *   A list of state IDs to get. If NULL then all states will be returned.
   *
   * @return \Drupal\workflows\StateInterface[]
   *   An array of workflow states, keyed by state IDs.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $state_ids contains a state ID that does not exist.
   */
  public function getStates($state_ids = NULL);

  /**
   * Gets a workflow state.
   *
   * @param string $state_id
   *   The state's ID.
   *
   * @return \Drupal\workflows\StateInterface
   *   The workflow state.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $state_id does not exist.
   */
  public function getState($state_id);

  /**
   * Sets a state's label.
   *
   * @param string $state_id
   *   The state ID to set the label for.
   * @param string $label
   *   The state's label.
   *
   * @return $this
   */
  public function setStateLabel($state_id, $label);

  /**
   * Sets a state's weight value.
   *
   * @param string $state_id
   *   The state ID to set the weight for.
   * @param int $weight
   *   The state's weight.
   *
   * @return $this
   */
  public function setStateWeight($state_id, $weight);

  /**
   * Deletes a state from the workflow.
   *
   * @param string $state_id
   *   The state ID to delete.
   *
   * @return $this
   *   The workflow type plugin.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $state_id does not exist.
   */
  public function deleteState($state_id);

  /**
   * Adds a transition to the workflow.
   *
   * @param string $id
   *   The transition ID.
   * @param string $label
   *   The transition's label.
   * @param array $from_state_ids
   *   The state IDs to transition from.
   * @param string $to_state_id
   *   The state ID to transition to.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if either state does not exist.
   */
  public function addTransition($id, $label, array $from_state_ids, $to_state_id);

  /**
   * Gets a transition object for the provided transition ID.
   *
   * @param string $transition_id
   *   A transition ID.
   *
   * @return \Drupal\workflows\TransitionInterface
   *   The transition.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $transition_id does not exist.
   */
  public function getTransition($transition_id);

  /**
   * Determines if a transition exists.
   *
   * @param string $transition_id
   *   The transition ID.
   *
   * @return bool
   *   TRUE if the transition exists, FALSE if not.
   */
  public function hasTransition($transition_id);

  /**
   * Gets transition objects for the provided transition IDs.
   *
   * @param string[] $transition_ids
   *   A list of transition IDs to get. If NULL then all transitions will be
   *   returned.
   *
   * @return \Drupal\workflows\TransitionInterface[]
   *   An array of transition objects.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $transition_ids contains a transition ID that does not exist.
   */
  public function getTransitions(?array $transition_ids = NULL);

  /**
   * Gets the transitions for a state for the provided direction.
   *
   * @param string $state_id
   *   The state to get transitions for.
   * @param string $direction
   *   (optional) The direction of the transition, defaults to
   *   TransitionInterface::DIRECTION_FROM. Possible values are:
   *   TransitionInterface::DIRECTION_FROM or TransitionInterface::DIRECTION_TO.
   *
   * @return \Drupal\workflows\TransitionInterface[]
   *   An array of the transition objects for the state in the given direction,
   *   keyed by transition ID.
   *
   * @see \Drupal\workflows\TransitionInterface::DIRECTION_FROM
   * @see \Drupal\workflows\TransitionInterface::DIRECTION_TO
   */
  public function getTransitionsForState($state_id, $direction = TransitionInterface::DIRECTION_FROM);

  /**
   * Gets a transition from state to state.
   *
   * @param string $from_state_id
   *   The state ID to transition from.
   * @param string $to_state_id
   *   The state ID to transition to.
   *
   * @return \Drupal\workflows\TransitionInterface
   *   The transitions.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the transition does not exist.
   */
  public function getTransitionFromStateToState($from_state_id, $to_state_id);

  /**
   * Determines if a transition from state to state exists.
   *
   * @param string $from_state_id
   *   The state ID to transition from.
   * @param string $to_state_id
   *   The state ID to transition to.
   *
   * @return bool
   *   TRUE if the transition exists, FALSE if not.
   */
  public function hasTransitionFromStateToState($from_state_id, $to_state_id);

  /**
   * Sets a transition's label.
   *
   * @param string $transition_id
   *   The transition ID.
   * @param string $label
   *   The transition's label.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if the transition does not exist.
   */
  public function setTransitionLabel($transition_id, $label);

  /**
   * Sets a transition's weight.
   *
   * @param string $transition_id
   *   The transition ID.
   * @param int $weight
   *   The transition's weight.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if the transition does not exist.
   */
  public function setTransitionWeight($transition_id, $weight);

  /**
   * Sets a transition's from states.
   *
   * @param string $transition_id
   *   The transition ID.
   * @param array $from_state_ids
   *   The state IDs to transition from.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if the transition does not exist or the states do not exist.
   */
  public function setTransitionFromStates($transition_id, array $from_state_ids);

  /**
   * Deletes a transition.
   *
   * @param string $transition_id
   *   The transition ID.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if the transition does not exist.
   */
  public function deleteTransition($transition_id);

}
