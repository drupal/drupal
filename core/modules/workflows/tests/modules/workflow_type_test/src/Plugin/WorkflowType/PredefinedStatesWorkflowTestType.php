<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\State;

/**
 * Test workflow type.
 *
 * @WorkflowType(
 *   id = "predefined_states_workflow_test_type",
 *   label = @Translation("Predefined States Workflow Test Type"),
 *   required_states = {
 *     "pay_blinds",
 *     "bet",
 *     "raise",
 *     "fold",
 *   }
 * )
 */
class PredefinedStatesWorkflowTestType extends WorkflowTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getStates($state_ids = NULL) {
    return array_filter([
      'pay_blinds' => new State($this, 'pay_blinds', 'Pay Blinds'),
      'bet' => new State($this, 'bet', 'Bet'),
      'raise' => new State($this, 'raise', 'Raise'),
      'fold' => new State($this, 'fold', 'Fold'),
    ], function($state) use ($state_ids) {
        return is_array($state_ids) ? in_array($state->id(), $state_ids) : TRUE;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getState($state_id) {
    $states = $this->getStates();
    if (!isset($states[$state_id])) {
      throw new \InvalidArgumentException("The state '$state_id' does not exist in workflow.'");
    }
    return $states[$state_id];
  }

  /**
   * {@inheritdoc}
   */
  public function hasState($state_id) {
    $states = $this->getStates();
    return isset($states[$state_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function addState($state_id, $label) {
    // States cannot be added on this workflow.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateLabel($state_id, $label) {
    // States cannot be altered on this workflow.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateWeight($state_id, $weight) {
    // States cannot be altered on this workflow.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteState($state_id) {
    // States cannot be deleted on this workflow.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'transitions' => [],
    ];
  }

}
