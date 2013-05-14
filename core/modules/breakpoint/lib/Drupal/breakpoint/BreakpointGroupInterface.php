<?php

/**
 * @file
 * Contains \Drupal\breakpoint\Plugin\Core\Entity\BreakpointGroupInterface.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a breakpoint group entity.
 */
interface BreakpointGroupInterface extends ConfigEntityInterface {

  /**
   * Checks if the breakpoint group is valid.
   *
   * @throws \Drupal\breakpoint\InvalidBreakpointSourceTypeException
   * @throws \Drupal\breakpoint\InvalidBreakpointSourceException
   *
   * @return bool
   *   Returns TRUE if the breakpoint group is valid.
   */
  public function isValid();

  /**
   * Adds a breakpoint using a name and a media query.
   *
   * @param string $name
   *   The name of the breakpoint.
   * @param string $media_query
   *   Media query.
   */
  public function addBreakpointFromMediaQuery($name, $media_query);

  /**
   * Adds one or more breakpoints to this group.
   *
   * The breakpoint name is either the machine_name or the ID of a breakpoint.
   *
   * @param array $breakpoints
   *   Array containing breakpoints keyed by their ID.
   */
  public function addBreakpoints($breakpoints);

}
