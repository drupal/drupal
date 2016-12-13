<?php

namespace Drupal\workflows;

/**
 * A transition value object that describes the transition between two states.
 *
 * @internal
 *   The workflow system is currently experimental and should only be leveraged
 *   by experimental modules and development releases of contributed modules.
 */
interface TransitionInterface {

  /**
   * Gets the transition's ID.
   *
   * @return string
   *   The transition's ID.
   */
  public function id();

  /**
   * Gets the transition's label.
   *
   * @return string
   *   The transition's label.
   */
  public function label();

  /**
   * Gets the transition's from states.
   *
   * @return \Drupal\workflows\StateInterface[]
   *   The transition's from states.
   */
  public function from();

  /**
   * Gets the transition's to state.
   *
   * @return \Drupal\workflows\StateInterface
   *   The transition's to state.
   */
  public function to();

  /**
   * Gets the transition's weight.
   *
   * @return string
   *   The transition's weight.
   */
  public function weight();

}
