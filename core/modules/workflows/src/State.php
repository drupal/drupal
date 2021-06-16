<?php

namespace Drupal\workflows;

/**
 * A value object representing a workflow state.
 */
class State implements StateInterface {

  /**
   * The workflow the state is attached to.
   *
   * @var \Drupal\workflows\WorkflowTypeInterface
   */
  protected $workflow;

  /**
   * The state's ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The state's label.
   *
   * @var string
   */
  protected $label;

  /**
   * The state's weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * State constructor.
   *
   * @param \Drupal\workflows\WorkflowTypeInterface $workflow
   *   The workflow the state is attached to.
   * @param string $id
   *   The state's ID.
   * @param string $label
   *   The state's label.
   * @param int $weight
   *   The state's weight.
   */
  public function __construct(WorkflowTypeInterface $workflow, $id, $label, $weight = 0) {
    $this->workflow = $workflow;
    $this->id = $id;
    $this->label = $label;
    $this->weight = $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function canTransitionTo($to_state_id) {
    return $this->workflow->hasTransitionFromStateToState($this->id, $to_state_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionTo($to_state_id) {
    if (!$this->canTransitionTo($to_state_id)) {
      throw new \InvalidArgumentException("Can not transition to '$to_state_id' state");
    }
    return $this->workflow->getTransitionFromStateToState($this->id(), $to_state_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions() {
    return $this->workflow->getTransitionsForState($this->id);
  }

  /**
   * Helper method to convert a State value object to a label.
   *
   * @param \Drupal\workflows\StateInterface $state
   *
   * @return string
   *   The label of the state.
   */
  public static function labelCallback(StateInterface $state) {
    return $state->label();
  }

}
