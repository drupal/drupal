<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Plugin\WorkflowTypeFormBase;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflow_type_test\DecoratedState;
use Drupal\workflow_type_test\DecoratedTransition;

/**
 * Test workflow type.
 *
 * @WorkflowType(
 *   id = "workflow_type_complex_test",
 *   label = @Translation("Workflow Type Complex Test"),
 * )
 */
class ComplexTestType extends WorkflowTypeFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function decorateState(StateInterface $state) {
    if (isset($this->configuration['states'][$state->id()])) {
      $state = new DecoratedState($state, $this->configuration['states'][$state->id()]['extra']);
    }
    else {
      $state = new DecoratedState($state);
    }
    return $state;
  }

  /**
   * {@inheritDoc}
   */
  public function decorateTransition(TransitionInterface $transition) {
    if (isset($this->configuration['transitions'][$transition->id()])) {
      $transition = new DecoratedTransition($transition, $this->configuration['transitions'][$transition->id()]['extra']);
    }
    else {
      $transition = new DecoratedTransition($transition);
    }
    return $transition;
  }

  /**
   * {@inheritdoc}
   */
  public function buildStateConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, StateInterface $state = NULL) {
    /** @var \Drupal\workflow_type_test\DecoratedState $state */
    $form = [];
    $form['extra'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra'),
      '#description' => $this->t('Extra information added to state'),
      '#default_value' => isset($state) ? $state->getExtra() : FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTransitionConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, TransitionInterface $transition = NULL) {
    /** @var \Drupal\workflow_type_test\DecoratedTransition $transition */
    $form = [];
    $form['extra'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra'),
      '#description' => $this->t('Extra information added to transition'),
      '#default_value' => isset($transition) ? $transition->getExtra() : FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // Always return TRUE to allow the logic in
    // \Drupal\workflows\Entity\Workflow::onDependencyRemoval() to be tested.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'example_setting' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['example_setting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example global workflow setting'),
      '#description' => $this->t('Extra information added to the workflow'),
      '#default_value' => $this->configuration['example_setting'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['example_setting'] = $form_state->getValue('example_setting');
  }

}
