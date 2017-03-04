<?php

namespace Drupal\Core\Image;

use Drupal\Core\ImageToolkit\ImageToolkitInterface;

/**
 * Defines an image object to represent an image file.
 *
 * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface
 * @see \Drupal\image\ImageEffectInterface
 *
 * @ingroup image
 */
class Image implements ImageInterface {

  /**
   * Path of the image file.
   *
   * @var string
   */
  protected $source = '';

  /**
   * An image toolkit object.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface
   */
  protected $toolkit;

  /**
   * File size in bytes.
   *
   * @var int
   */
  protected $fileSize;

  /**
   * Constructs a new Image object.
   *
   * @param \Drupal\Core\ImageToolkit\ImageToolkitInterface $toolkit
   *   The image toolkit.
   * @param string|null $source
   *   (optional) The path to an image file, or NULL to construct the object
   *   with no image source.
   */
  public function __construct(ImageToolkitInterface $toolkit, $source = NULL) {
    $this->toolkit = $toolkit;
    if ($source) {
      $this->source = $source;
      $this->getToolkit()->setSource($this->source);
      // Defer image file validity check to the toolkit.
      if ($this->getToolkit()->parseFile()) {
        $this->fileSize = filesize($this->source);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return $this->getToolkit()->isValid();
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->getToolkit()->getHeight();
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->getToolkit()->getWidth();
  }

  /**
   * {@inheritdoc}
   */
  public function getFileSize() {
    return $this->fileSize;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->getToolkit()->getMimeType();
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getToolkitId() {
    return $this->getToolkit()->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getToolkit() {
    return $this->toolkit;
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination = NULL) {
    // Return immediately if the image is not valid.
    if (!$this->isValid()) {
      return FALSE;
    }

    $destination = $destination ?: $this->getSource();
    if ($return = $this->getToolkit()->save($destination)) {
      // Clear the cached file size and refresh the image information.
      clearstatcache(TRUE, $destination);
      $this->fileSize = filesize($destination);
      $this->source = $destination;

      // @todo Use File utility when https://www.drupal.org/node/2050759 is in.
      if ($this->chmod($destination)) {
        return $return;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function apply($operation, array $arguments = []) {
    return $this->getToolkit()->apply($operation, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function createNew($width, $height, $extension = 'png', $transparent_color = '#ffffff') {
    return $this->apply('create_new', ['width' => $width, 'height' => $height, 'extension' => $extension, 'transparent_color' => $transparent_color]);
  }

  /**
   * {@inheritdoc}
   */
  public function convert($extension) {
    return $this->apply('convert', ['extension' => $extension]);
  }

  /**
   * {@inheritdoc}
   */
  public function crop($x, $y, $width, $height = NULL) {
    return $this->apply('crop', ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);
  }

  /**
   * {@inheritdoc}
   */
  public function desaturate() {
    return $this->apply('desaturate', []);
  }

  /**
   * {@inheritdoc}
   */
  public function resize($width, $height) {
    return $this->apply('resize', ['width' => $width, 'height' => $height]);
  }

  /**
   * {@inheritdoc}
   */
  public function rotate($degrees, $background = NULL) {
    return $this->apply('rotate', ['degrees' => $degrees, 'background' => $background]);
  }

  /**
   * {@inheritdoc}
   */
  public function scaleAndCrop($width, $height) {
    return $this->apply('scale_and_crop', ['width' => $width, 'height' => $height]);
  }

  /**
   * {@inheritdoc}
   */
  public function scale($width, $height = NULL, $upscale = FALSE) {
    return $this->apply('scale', ['width' => $width, 'height' => $height, 'upscale' => $upscale]);
  }

  /**
   * Provides a wrapper for drupal_chmod() to allow unit testing.
   *
   * @param string $uri
   *   A string containing a URI file, or directory path.
   * @param int $mode
   *   Integer value for the permissions. Consult PHP chmod() documentation for
   *   more information.
   *
   * @see drupal_chmod()
   *
   * @todo Remove when https://www.drupal.org/node/2050759 is in.
   *
   * @return bool
   *   TRUE for success, FALSE in the event of an error.
   */
  protected function chmod($uri, $mode = NULL) {
    return drupal_chmod($uri, $mode);
  }

}
