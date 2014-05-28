<?php

/**
 * @file
 * Contains \Drupal\responsive_image\ResponsiveImageMappingInterface.
 */

namespace Drupal\responsive_image;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a responsive_image mapping entity.
 */
interface ResponsiveImageMappingInterface extends ConfigEntityInterface {

  /**
   * Checks if there is at least one mapping defined.
   *
   * return bool
   *   Whether the entity has any responsive_image mappings.
   */
  public function hasMappings();

  /**
   * Sets the mappings for the responsive_image mapping.
   *
   * The array is keyed by the Breakpoint Group Id and then then by each
   * Breakpoints multipliers within the Breakpoint Group.
   *
   * @param array[] $mappings
   *   The mappings the responsive_image mapping will be set with.
   *
   * @return $this
   */
  public function setMappings(array $mappings);

  /**
   * Returns the mappings for the responsive_image mapping.
   *
   * @return array[]
   *   The responsive_imagemappings.
   */
  public function getMappings();

  /**
   * Sets the breakpoint group for the responsive_image mapping.
   *
   * @param \Drupal\breakpoint\Entity\BreakpointGroup $breakpoint_group
   *   The responsive_image mappings breakpoint group.
   *
   * @return $this
   */
  public function setBreakpointGroup($breakpoint_group);

  /**
   * Returns the breakpoint group for the responsive_image mapping.
   *
   * @return \Drupal\breakpoint\Entity\BreakpointGroup
   *   The responsive_image mappings breakpoint group.
   */
  public function getBreakpointGroup();

}
