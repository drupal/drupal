<?php

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
    $this->toolkitId = $this->toolkitManager->getDefaultToolkitId();
  }

  /**
   * Sets the ID of the image toolkit.
   *
   * @param string $toolkit_id
   *   The ID of the image toolkit to use for this image factory.
   *
   * @return $this
   */
  public function setToolkitId($toolkit_id) {
    $this->toolkitId = $toolkit_id;
    return $this;
  }

  /**
   * Gets the ID of the image toolkit currently in use.
   *
   * @return string
   *   The ID of the image toolkit in use by the image factory.
   */
  public function getToolkitId() {
    return $this->toolkitId;
  }

  /**
   * Constructs a new Image object.
   *
   * Normally, the toolkit set as default in the admin UI is used by the
   * factory to create new Image objects. This can be overridden through
   * \Drupal\Core\Image\ImageInterface::setToolkitId() so that any new Image
   * object created will use the new toolkit specified. Finally, a single
   * Image object can be created using a specific toolkit, regardless of the
   * current factory settings, by passing its plugin ID in the $toolkit_id
   * argument.
   *
   * @param string|null $source
   *   (optional) The path to an image file, or NULL to construct the object
   *   with no image source.
   * @param string|null $toolkit_id
   *   (optional) The ID of the image toolkit to use for this image, or NULL
   *   to use the current toolkit.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   An Image object.
   *
   * @see ImageFactory::setToolkitId()
   */
  public function get($source = NULL, $toolkit_id = NULL) {
    $toolkit_id = $toolkit_id ?: $this->toolkitId;
    return new Image($this->toolkitManager->createInstance($toolkit_id), $source);
  }

  /**
   * Returns the image file extensions supported by the toolkit.
   *
   * @param string|null $toolkit_id
   *   (optional) The ID of the image toolkit to use for checking, or NULL
   *   to use the current toolkit.
   *
   * @return array
   *   An array of supported image file extensions (e.g. png/jpeg/gif).
   *
   * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface::getSupportedExtensions()
   */
  public function getSupportedExtensions($toolkit_id = NULL) {
    $toolkit_id = $toolkit_id ?: $this->toolkitId;
    $definition = $this->toolkitManager->getDefinition($toolkit_id);
    return call_user_func($definition['class'] . '::getSupportedExtensions');
  }

}
