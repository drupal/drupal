<?php

namespace Drupal\workflows\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * A base class for Workflow type plugins.
 *
 * @see \Drupal\workflows\Annotation\WorkflowType
 *
 * @internal
 *   The workflow system is currently experimental and should only be leveraged
 *   by experimental modules and development releases of contributed modules.
 */
abstract class WorkflowTypeBase extends PluginBase implements WorkflowTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function initializeWorkflow(WorkflowInterface $workflow) {
    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $definition = $this->getPluginDefinition();
    // The label can be an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return $definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function checkWorkflowAccess(WorkflowInterface $entity, $operation, AccountInterface $account) {
    return AccessResult::neutral();
  }

  /**
   * {@inheritDoc}
   */
  public function decorateState(StateInterface $state) {
    return $state;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteState($state_id) {
    unset($this->configuration['states'][$state_id]);
  }

  /**
   * {@inheritDoc}
   */
  public function decorateTransition(TransitionInterface $transition) {
    return $transition;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteTransition($transition_id) {
    unset($this->configuration['transitions'][$transition_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildStateConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, StateInterface $state = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildTransitionConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, TransitionInterface $transition = NULL) {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritDoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredStates() {
    return $this->getPluginDefinition()['required_states'];
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [],
      'transitions' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialState(WorkflowInterface $workflow) {
    $ordered_states = $workflow->getStates();
    return reset($ordered_states);
  }

}
