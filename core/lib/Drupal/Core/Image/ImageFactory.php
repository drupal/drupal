<?php

/**
 * @file
 * Contains Drupal\Core\Image\ImageFactory.
 */

namespace Drupal\Core\Image;

use Drupal\Core\ImageToolkit\ImageToolkitManager;

/**
 * Provides a factory for image objects.
 */
class ImageFactory {

  /**
   * The image toolkit plugin manager.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitManager
   */
  protected $toolkitManager;

  /**
   * The image toolkit ID to use for this factory.
   *
   * @var string
   */
  protected $toolkitId;

  /**
   * Constructs a new ImageFactory object.
   *
   * @param \Drupal\Core\ImageToolkit\ImageToolkitManager $toolkit_manager
   *   The image toolkit plugin manager.
   */
  public function __construct(ImageToolkitManager $toolkit_manager) {
    $this->toolkitManager = $toolkit_manager;
  }

  /**
   * Sets this factory image toolkit ID.
   *
   * @param string $toolkit_id
   *   The image toolkit ID to use for this image factory.
   *
   * @return self
   *   Returns this image.
   */
  public function setToolkitId($toolkit_id) {
    $this->toolkitId = $toolkit_id;
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
    if (!$this->toolkitId) {
      $this->toolkitId = $this->toolkitManager->getDefaultToolkitId();
    }
    return new Image($source, $this->toolkitManager->createInstance($this->toolkitId));
  }

}
