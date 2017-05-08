<?php

namespace Drupal\workflows;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * An interface for Workflow type plugins.
 *
 * @internal
 *   The workflow system is currently experimental and should only be leveraged
 *   by experimental modules and development releases of contributed modules.
 */
interface WorkflowTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurablePluginInterface {

  /**
   * Initializes a workflow.
   *
   * Used to create required states and default transitions.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow to initialize.
   *
   * @return \Drupal\workflows\WorkflowInterface
   *   The initialized workflow.
   *
   * @see \Drupal\workflows\Form\WorkflowAddForm::save()
   */
  public function initializeWorkflow(WorkflowInterface $workflow);

  /**
   * Gets the label for the workflow type.
   *
   * @return string
   *   The workflow type label.
   */
  public function label();

  /**
   * Performs access checks.
   *
   * @param \Drupal\workflows\WorkflowInterface $entity
   *   The workflow entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkWorkflowAccess(WorkflowInterface $entity, $operation, AccountInterface $account);

  /**
   * Decorates states so the WorkflowType can add additional information.
   *
   * @param \Drupal\workflows\StateInterface $state
   *   The state object to decorate.
   *
   * @return \Drupal\workflows\StateInterface
   *   The decorated state object.
   */
  public function decorateState(StateInterface $state);

  /**
   * React to the removal of a state from a workflow.
   *
   * @param string $state_id
   *   The state ID of the state that is being removed.
   */
  public function deleteState($state_id);

  /**
   * Decorates transitions so the WorkflowType can add additional information.
   * @param \Drupal\workflows\TransitionInterface $transition
   *   The transition object to decorate.
   *
   * @return \Drupal\workflows\TransitionInterface
   *   The decorated transition object.
   */
  public function decorateTransition(TransitionInterface $transition);

  /**
   * React to the removal of a transition from a workflow.
   *
   * @param string $transition_id
   *   The transition ID of the transition that is being removed.
   */
  public function deleteTransition($transition_id);

  /**
   * Gets the initial state for the workflow.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow entity.
   *
   * @return \Drupal\workflows\StateInterface
   *   The initial state.
   */
  public function getInitialState(WorkflowInterface $workflow);

  /**
   * Builds a form to be added to the Workflow state edit form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow the state is attached to.
   * @param \Drupal\workflows\StateInterface|null $state
   *   The workflow state being edited. If NULL, a new state is being added.
   *
   * @return array
   *   Form elements to add to a workflow state form for customisations to the
   *   workflow.
   *
   * @see \Drupal\workflows\Form\WorkflowStateAddForm::form()
   * @see \Drupal\workflows\Form\WorkflowStateEditForm::form()
   */
  public function buildStateConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, StateInterface $state = NULL);

  /**
   * Builds a form to be added to the Workflow transition edit form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow the state is attached to.
   * @param \Drupal\workflows\TransitionInterface|null $transition
   *   The workflow transition being edited. If NULL, a new transition is being
   *   added.
   *
   * @return array
   *   Form elements to add to a workflow transition form for customisations to
   *   the workflow.
   *
   * @see \Drupal\workflows\Form\WorkflowTransitionAddForm::form()
   * @see \Drupal\workflows\Form\WorkflowTransitionEditForm::form()
   */
  public function buildTransitionConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, TransitionInterface $transition = NULL);

  /**
   * Gets the required states of workflow type.
   *
   * This are usually configured in the workflow type annotation.
   *
   * @return array[]
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

}
