<?php

namespace Drupal\responsive_image;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a responsive_image mapping entity.
 */
interface ResponsiveImageStyleInterface extends ConfigEntityInterface {

  /**
   * The machine name for the empty image breakpoint image style option.
   */
  const EMPTY_IMAGE = '_empty image_';

  /**
   * The machine name for the original image breakpoint image style option.
   */
  const ORIGINAL_IMAGE = '_original image_';

  /**
   * Checks if there is at least one mapping defined.
   *
   * @return bool
   *   Whether the entity has any image style mappings.
   */
  public function hasImageStyleMappings();

  /**
   * Returns the mappings of breakpoint ID and multiplier to image style.
   *
   * @return array[]
   *   The image style mappings. Keyed by breakpoint ID then multiplier.
   *   The value is the image style mapping array with following keys:
   *     - image_mapping_type: Either 'image_style' or 'sizes'.
   *     - image_mapping:
   *       - If image_mapping_type is 'image_style', the image style ID.
   *       - If image_mapping_type is 'sizes', an array with following keys:
   *         - sizes: The value for the 'sizes' attribute.
   *         - sizes_image_styles: The image styles to use for the 'srcset'
   *           attribute.
   *     - breakpoint_id: The breakpoint ID for this mapping.
   *     - multiplier: The multiplier for this mapping.
   */
  public function getKeyedImageStyleMappings();

  /**
   * Returns the image style mappings for the responsive image style.
   *
   * @return array[]
   *   An array of image style mappings. Each image style mapping array
   *   contains the following keys:
   *   - breakpoint_id
   *   - multiplier
   *   - image_mapping_type
   *   - image_mapping
   */
  public function getImageStyleMappings();

  /**
   * Sets the breakpoint group for the responsive image style.
   *
   * @param string $breakpoint_group
   *   The responsive image style breakpoint group.
   *
   * @return $this
   */
  public function setBreakpointGroup($breakpoint_group);

  /**
   * Returns the breakpoint group for the responsive image style.
   *
   * @return string
   *   The breakpoint group.
   */
  public function getBreakpointGroup();

  /**
   * Sets the fallback image style for the responsive image style.
   *
   * @param string $fallback_image_style
   *   The fallback image style ID.
   *
   * @return $this
   */
  public function setFallbackImageStyle($fallback_image_style);

  /**
   * Returns the fallback image style ID for the responsive image style.
   *
   * @return string
   *   The fallback image style ID.
   */
  public function getFallbackImageStyle();

  /**
   * Gets the image style mapping for a breakpoint ID and multiplier.
   *
   * @param string $breakpoint_id
   *   The breakpoint ID.
   * @param string $multiplier
   *   The multiplier.
   *
   * @return array|null
   *   The image style mapping. NULL if the mapping does not exist.
   *   The image style mapping has following keys:
   *     - image_mapping_type: Either 'image_style' or 'sizes'.
   *     - image_mapping:
   *       - If image_mapping_type is 'image_style', the image style ID.
   *       - If image_mapping_type is 'sizes', an array with following keys:
   *         - sizes: The value for the 'sizes' attribute.
   *         - sizes_image_styles: The image styles to use for the 'srcset'
   *           attribute.
   *     - breakpoint_id: The breakpoint ID for this image style mapping.
   *     - multiplier: The multiplier for this image style mapping.
   */
  public function getImageStyleMapping($breakpoint_id, $multiplier);

  /**
   * Checks if there is at least one image style mapping defined.
   *
   * @param array $image_style_mapping
   *   The image style mapping.
   *
   * @return bool
   *   Whether the image style mapping is empty.
   */
  public static function isEmptyImageStyleMapping(array $image_style_mapping);

  /**
   * Adds an image style mapping to the responsive image configuration entity.
   *
   * @param string $breakpoint_id
   *   The breakpoint ID.
   * @param string $multiplier
   *   The multiplier.
   * @param array $image_style_mapping
   *   The mapping image style mapping.
   *
   * @return $this
   */
  public function addImageStyleMapping($breakpoint_id, $multiplier, array $image_style_mapping);

  /**
   * Removes all image style mappings from the responsive image style.
   *
   * @return $this
   */
  public function removeImageStyleMappings();

  /**
   * Gets all the image styles IDs involved in the responsive image mapping.
   *
   * @return string[]
   *   The image styles IDs.
   */
  public function getImageStyleIds();

}
