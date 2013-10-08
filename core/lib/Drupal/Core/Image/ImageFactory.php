<?php

/**
 * @file
 * Contains Drupal\Core\Image\ImageFactory.
 */

namespace Drupal\Core\Image;

use Drupal\Core\ImageToolkit\ImageToolkitInterface;

/**
 * Provides a factory for image objects.
 */
class ImageFactory {

  /**
   * The image toolkit to use for this factory.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface
   */
  protected $toolkit;

  /**
   * Constructs a new ImageFactory object.
   *
   * @param \Drupal\Core\ImageToolkit\ImageToolkitInterface $toolkit
   *   The image toolkit to use for this image factory.
   */
  public function __construct(ImageToolkitInterface $toolkit) {
    $this->toolkit = $toolkit;
  }

  /**
   * Sets a custom image toolkit.
   *
   * @param \Drupal\Core\ImageToolkit\ImageToolkitInterface $toolkit
   *   The image toolkit to use for this image factory.
   *
   * @return self
   *   Returns this image.
   */
  public function setToolkit(ImageToolkitInterface $toolkit) {
    $this->toolkit = $toolkit;
    return $this;
  }

  /**
   * Constructs a new Image object.
   *
   * @param string $source
   *   The path to an image file.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The new Image object.
   */
  public function get($source) {
    return new Image($source, $this->toolkit);
  }

}
