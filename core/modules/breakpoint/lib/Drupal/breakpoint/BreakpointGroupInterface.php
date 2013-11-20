<?php

/**
 * @file
 * Contains \Drupal\breakpoint\Entity\BreakpointGroupInterface.
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
   *   Array containing breakpoint objects
   *
   * @return \Drupal\breakpoint\Entity\BreakpointGroup
   *   The breakpoint group object.
   */
  public function addBreakpoints($breakpoints);

  /**
   * Gets the array of breakpoints for the breakpoint group.
   *
   * @return array
   *   The array of breakpoints for the breakpoint group.
   */
  public function getBreakpoints();

  /**
   * Gets a breakpoint from the breakpoint group by ID.
   *
   * @param string $id
   *   The breakpoint ID to get.
   *
   * @return \Drupal\breakpoint\Entity\Breakpoint|boolean
   *   The breakpoint or FALSE if not in the Breakpoint group.
   */
  public function getBreakpointById($id);

}
