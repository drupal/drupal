<?php

namespace Drupal\workflows;

/**
 * A transition value object that describes the transition between two states.
 *
 * @internal
 *   The TransitionInterface should only be used by Workflows and Content
 *   Moderation.
 *
 * @todo Revisit the need for this in https://www.drupal.org/node/2902309.
 */
interface TransitionInterface {

  /**
   * The key of the transition plugin form.
   */
  const PLUGIN_FORM_KEY = 'transition';

  /**
   * The transition direction from.
   */
  const DIRECTION_FROM = 'from';

  /**
   * The transition direction to.
   */
  const DIRECTION_TO = 'to';

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
