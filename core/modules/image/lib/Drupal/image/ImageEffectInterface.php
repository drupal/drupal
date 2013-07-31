<?php

/**
 * @file
 * Contains \Drupal\image\ImageEffectInterface.
 */

namespace Drupal\image;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for image effects.
 */
interface ImageEffectInterface extends PluginInspectionInterface {

  /**
   * Applies an image effect to the image object.
   *
   * @param \stdClass $image
   *   An image object returned by image_load().
   *
   * @return bool
   *   TRUE on success. FALSE if unable to perform the image effect on the image.
   */
  public function applyEffect($image);

  /**
   * Determines the dimensions of the styled image.
   *
   * @param array $dimensions
   *   Dimensions to be modified - an array with components width and height, in
   *   pixels.
   */
  public function transformDimensions(array &$dimensions);

  /**
   * Returns a render array summarizing the configuration of the image effect.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns the image effect label.
   *
   * @return string
   *   The image effect label.
   */
  public function label();

  /**
   * Returns the unique ID representing the image effect.
   *
   * @return string
   *   The image effect ID.
   */
  public function getUuid();

  /**
   * Returns the weight of the image effect.
   *
   * @return int|string
   *   Either the integer weight of the image effect, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this image effect.
   *
   * @param int $weight
   *   The weight for this image effect.
   *
   * @return self
   *   This image effect.
   */
  public function setWeight($weight);

  /**
   * Exports the complete configuration of this image effect instance.
   *
   * @return array
   */
  public function export();

  /**
   * Sets the configuration for this image effect.
   *
   * @param array $configuration
   *   An associative array containing:
   *   - uuid: (optional) The image effect ID.
   *   - weight: (optional) The weight of the image effect.
   *   - data: (optional) An array of configuration specific to this image
   *     effect type.
   *
   * @return self
   *   This image effect.
   */
  public function setPluginConfiguration(array $configuration);

}
