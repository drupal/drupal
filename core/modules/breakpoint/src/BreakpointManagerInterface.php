<?php

namespace Drupal\breakpoint;

/**
 * Defines an interface for breakpoint managers.
 */
interface BreakpointManagerInterface {

  /**
   * Gets breakpoints for the specified group.
   *
   * @param string $group
   *   The breakpoint group to retrieve.
   *
   * @return \Drupal\breakpoint\BreakpointInterface[]
   *   Array of breakpoint plugins keyed by machine name.
   */
  public function getBreakpointsByGroup($group);

  /**
   * Gets all the existing breakpoint groups.
   *
   * @return array
   *   Array of breakpoint group labels. Keyed by group name.
   */
  public function getGroups();

  /**
   * Gets all the providers for the specified breakpoint group.
   *
   * @param string $group
   *   The breakpoint group to retrieve.
   *
   * @return array
   *   An array keyed by provider name with values of provider type (module or
   *   theme).
   */
  public function getGroupProviders($group);

}
