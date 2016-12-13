<?php

namespace Drupal\workflow_type_test;

use Drupal\workflows\TransitionInterface;

/**
 * A value object representing a workflow transition.
 */
class DecoratedTransition implements TransitionInterface {

  /**
   * The vanilla transition object from the Workflow module.
   *
   * @var \Drupal\workflows\TransitionInterface
   */
  protected $transition;

  /**
   * Extra information added to transition.
   *
   * @var string
   */
  protected $extra;

  /**
   * DecoratedTransition constructor.
   *
   * @param \Drupal\workflows\TransitionInterface $transition
   *   The vanilla transition object from the Workflow module.
   * @param string $extra
   *   (optional) Extra information stored on the transition. Defaults to ''.
   */
  public function __construct(TransitionInterface $transition, $extra = '') {
    $this->transition = $transition;
    $this->extra = $extra;
  }

  /**
   * Gets the extra information stored on the transition.
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
    return $this->transition->id();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->transition->label();
  }

  /**
   * {@inheritdoc}
   */
  public function from() {
    return $this->transition->from();
  }

  /**
   * {@inheritdoc}
   */
  public function to() {
    return $this->transition->to();
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->transition->weight();
  }

}
