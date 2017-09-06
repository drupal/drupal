<?php

namespace Drupal\workflows;

/**
 * A transition value object that describes the transition between states.
 */
class Transition implements TransitionInterface {

  /**
   * The workflow that this transition is attached to.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $workflow;

  /**
   * The transition's ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The transition's label.
   *
   * @var string
   */
  protected $label;

  /**
   * The transition's from state IDs.
   *
   * @var string[]
   */
  protected $fromStateIds;

  /**
   * The transition's to state ID.
   *
   * @var string
   */
  protected $toStateId;

  /**
   * The transition's weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * Transition constructor.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow the state is attached to.
   * @param string $id
   *   The transition's ID.
   * @param string $label
   *   The transition's label.
   * @param array $from_state_ids
   *   A list of from state IDs.
   * @param string $to_state_id
   *   The to state ID.
   * @param int $weight
   *   (optional) The transition's weight. Defaults to 0.
   */
  public function __construct(WorkflowTypeInterface $workflow, $id, $label, array $from_state_ids, $to_state_id, $weight = 0) {
    $this->workflow = $workflow;
    $this->id = $id;
    $this->label = $label;
    $this->fromStateIds = $from_state_ids;
    $this->toStateId = $to_state_id;
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
  public function from() {
    return $this->workflow->getStates($this->fromStateIds);
  }

  /**
   * {@inheritdoc}
   */
  public function to() {
    return $this->workflow->getState($this->toStateId);
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->weight;
  }

}
