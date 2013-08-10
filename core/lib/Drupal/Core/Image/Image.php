<?php

/**
 * @file
 * Contains \Drupal\Core\Image\Image.
 */

namespace Drupal\Core\Image;

use Drupal\system\Plugin\ImageToolkitInterface;
use Drupal\Component\Utility\Image as ImageUtility;

/**
 * Defines an image object to represent an image file.
 *
 * @see \Drupal\system\Plugin\ImageToolkitInterface
 * @see \Drupal\image\ImageEffectInterface
 *
 * @ingroup image
 */
class Image implements ImageInterface {

  /**
   * String specifying the path of the image file.
   *
   * @var string
   */
  protected $source;

  /**
   * An image toolkit object.
   *
   * @var \Drupal\system\Plugin\ImageToolkitInterface
   */
  protected $toolkit;

  /**
   * An image file handle.
   *
   * @var resource
   */
  protected $resource;

  /**
   * Height, in pixels.
   *
   * @var int
   */
  protected $height = 0;

  /**
   * Width, in pixels.
   *
   * @var int
   */
  protected $width = 0;

  /**
   * Commonly used file extension for the image.
   *
   * @var string
   */
  protected $extension = '';

  /**
   * MIME type ('image/jpeg', 'image/gif', 'image/png').
   *
   * @var string
   */
  protected $mimeType = '';

  /**
   * File size in bytes.
   *
   * @var int
   */
  protected $fileSize = 0;

  /**
   * If this image file has been processed.
   *
   * @var bool
   */
  protected $processed = FALSE;

  /**
   * Constructs a new Image object.
   *
   * @param string $source
   *   The path to an image file.
   * @param \Drupal\system\Plugin\ImageToolkitInterface $toolkit
   *   The image toolkit.
   */
  public function __construct($source, ImageToolkitInterface $toolkit) {
    $this->source = $source;
    $this->toolkit = $toolkit;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension() {
    $this->processInfo();
    return $this->extension;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    $this->processInfo();
    return $this->height;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeight($height) {
    $this->height = $height;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    $this->processInfo();
    return $this->width;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidth($width) {
    $this->width = $width;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileSize() {
    $this->processInfo();
    return $this->fileSize;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    $this->processInfo();
    return $this->mimeType;
  }

  /**
   * {@inheritdoc}
   */
  public function setResource($resource) {
    $this->resource = $resource;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasResource() {
    return (bool) $this->resource;
  }

  /**
   * {@inheritdoc}
   */
  public function getResource() {
    if (!$this->hasResource()) {
      $this->processInfo();
      $this->toolkit->load($this);
    }
    return $this->resource;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->source = $source;
    return $this;
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
    return $this->toolkit->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination = NULL) {
    if (empty($destination)) {
      $destination = $this->getSource();
    }
    if ($return = $this->toolkit->save($this, $destination)) {
      // Clear the cached file size and refresh the image information.
      clearstatcache(TRUE, $destination);
      $this->setSource($destination);
      $this->processInfo();

      // @todo Use File utility when https://drupal.org/node/2050759 is in.
      if ($this->chmod($destination)) {
        return $return;
      }
    }
    return FALSE;
  }

  /**
   * Prepares the image information.
   *
   * Drupal supports GIF, JPG and PNG file formats when used with the GD
   * toolkit, and may support others, depending on which toolkits are
   * installed.
   *
   * @return bool
   *   FALSE, if the file could not be found or is not an image. Otherwise, the
   *   image information is populated.
   */
  protected function processInfo() {
    if ($this->processed) {
      return TRUE;
    }

    $destination = $this->getSource();
    if (!is_file($destination) && !is_uploaded_file($destination)) {
      return FALSE;
    }

    if ($details = $this->toolkit->getInfo($this)) {
      $this->height = $details['height'];
      $this->width = $details['width'];
      $this->extension = $details['extension'];
      $this->mimeType = $details['mime_type'];
      $this->fileSize = filesize($destination);
      $this->processed = TRUE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function scale($width = NULL, $height = NULL, $upscale = FALSE) {
    $dimensions = array(
      'width' => $this->getWidth(),
      'height' => $this->getHeight(),
    );

    // Scale the dimensions - if they don't change then just return success.
    if (!ImageUtility::scaleDimensions($dimensions, $width, $height, $upscale)) {
      return TRUE;
    }

    return $this->resize($dimensions['width'], $dimensions['height']);

  }

  /**
   * {@inheritdoc}
   */
  public function scaleAndCrop($width, $height) {
    $scale = max($width / $this->getWidth(), $height / $this->getHeight());
    $x = ($this->getWidth() * $scale - $width) / 2;
    $y = ($this->getHeight() * $scale - $height) / 2;

    if ($this->resize($this->getWidth() * $scale, $this->getHeight() * $scale)) {
      return $this->crop($x, $y, $width, $height);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function crop($x, $y, $width, $height) {
    $aspect = $this->getHeight() / $this->getWidth();
    if (empty($height)) $height = $width * $aspect;
    if (empty($width)) $width = $height / $aspect;

    $width = (int) round($width);
    $height = (int) round($height);

    return $this->toolkit->crop($this, $x, $y, $width, $height);
  }

  /**
   * {@inheritdoc}
   */
  public function resize($width, $height) {
    $width = (int) round($width);
    $height = (int) round($height);

    return $this->toolkit->resize($this, $width, $height);
  }

  /**
   * {@inheritdoc}
   */
  public function desaturate() {
    return $this->toolkit->desaturate($this);
  }

  /**
   * {@inheritdoc}
   */
  public function rotate($degrees, $background = NULL) {
    return $this->toolkit->rotate($this, $degrees, $background);
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
   * @todo Remove when https://drupal.org/node/2050759 is in.
   *
   * @return bool
   *   TRUE for success, FALSE in the event of an error.
   */
  protected function chmod($uri, $mode = NULL) {
    return drupal_chmod($uri, $mode);
  }

}
