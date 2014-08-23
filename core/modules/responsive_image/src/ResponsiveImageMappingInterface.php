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
   *   Whether the entity has any responsive image mappings.
   */
  public function hasMappings();

  /**
   * Returns the mappings of breakpoint ID and multiplier to image style.
   *
   * @return array[]
   *   The responsive image mappings. Keyed by breakpoint ID then multiplier.
   *   The value is the image style ID.
   */
  public function getKeyedMappings();

  /**
   * Returns the mappings for the responsive image mapping.
   *
   * @return array[]
   *   An array responsive image mappings. Each responsive mapping array
   *   contains the following keys:
   *   - breakpoint_id
   *   - multiplier
   *   - image_style
   */
  public function getMappings();

  /**
   * Sets the breakpoint group for the responsive_image mapping.
   *
   * @param string $breakpoint_group
   *   The responsive image mapping breakpoint group.
   *
   * @return $this
   */
  public function setBreakpointGroup($breakpoint_group);

  /**
   * Returns the breakpoint group for the responsive image mapping.
   *
   * @return string
   *   The breakpoint group.
   */
  public function getBreakpointGroup();

  /**
   * Gets the image style ID for a breakpoint ID and multiplier.
   *
   * @param string $breakpoint_id
   *   The breakpoint ID.
   * @param string $multiplier
   *   The multiplier.
   *
   * @return string|null
   *   The image style ID. Null if the mapping does not exist.
   */
  public function getImageStyle($breakpoint_id, $multiplier);

  /**
   * Adds a mapping to the responsive image configuration entity.
   *
   * @param string $breakpoint_id
   *   The breakpoint ID.
   * @param string $multiplier
   *   The multiplier.
   * @param string $image_style
   *   The image style ID.
   *
   * @return $this
   */
  public function addMapping($breakpoint_id, $multiplier, $image_style);

  /**
   * Removes all mappings from the responsive image configuration entity.
   *
   * @return $this
   */
  public function removeMappings();

}
