<?php

namespace Drupal\breakpoint;

/**
 * Interface for Breakpoint plugins.
 */
interface BreakpointInterface {

  /**
   * Returns the translated label.
   *
   * @return string
   *   The translated label.
   */
  public function getLabel();

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Returns the media query.
   *
   * @return string
   *   The media query.
   */
  public function getMediaQuery();

  /**
   * Returns the multipliers.
   *
   * @return array
   *   The multipliers.
   */
  public function getMultipliers();

  /**
   * Returns the provider.
   *
   * @return string
   *   The provider.
   */
  public function getProvider();

  /**
   * Returns the breakpoint group.
   *
   * @return string
   *   The breakpoint group.
   */
  public function getGroup();

}
