<?php

namespace Drupal\workflow_type_test;

use Drupal\workflows\StateInterface;

/**
 * A value object representing a workflow state.
 */
class DecoratedState implements StateInterface {

  /**
   * The vanilla state object from the Workflow module.
   *
   * @var \Drupal\workflows\StateInterface
   */
  protected $state;

  /**
   * Extra information added to state.
   *
   * @var string
   */
  protected $extra;

  /**
   * DecoratedState constructor.
   *
   * @param \Drupal\workflows\StateInterface $state
   *   The vanilla state object from the Workflow module.
   * @param string $extra
   *   (optional) Extra information stored on the state. Defaults to ''.
   */
  public function __construct(StateInterface $state, $extra = '') {
    $this->state = $state;
    $this->extra = $extra;
  }

  /**
   * Gets the extra information stored on the state.
   *
   * @return string
   */
  public function getExtra() {
    return $this->extra;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->state->id();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->state->label();
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->state->weight();
  }

  /**
   * {@inheritdoc}
   */
  public function canTransitionTo($to_state_id) {
    return $this->state->canTransitionTo($to_state_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionTo($to_state_id) {
    return $this->state->getTransitionTo($to_state_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions() {
    return $this->state->getTransitions();
  }

}
