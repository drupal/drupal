<?php

/**
 * @file
 * Contains \Drupal\Core\Image\Image.
 */

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
   * String specifying the path of the image file.
   *
   * @var string
   */
  protected $source;

  /**
   * An image toolkit object.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface
   */
  protected $toolkit;

  /**
   * Image type represented by a PHP IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG).
   *
   * @var int
   */
  protected $type;

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
      $this->processInfo();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported() {
    return in_array($this->getType(), $this->toolkit->supportedTypes());
  }

  /**
   * {@inheritdoc}
   */
  public function isExisting() {
    $this->processInfo();
    return $this->processed;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    $this->processInfo();
    return $this->toolkit->getHeight($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    $this->processInfo();
    return $this->toolkit->getWidth($this);
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
  public function getType() {
    $this->processInfo();
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    $this->processInfo();
    return $this->type ? image_type_to_mime_type($this->type) : '';
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
  public function getToolkit() {
    $this->processInfo();
    return $this->toolkit;
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
      $this->type = $details['type'];
      $this->fileSize = filesize($destination);

      $this->processed = TRUE;
    }
    return TRUE;
  }

  /**
   * Passes through calls that represent image toolkit operations onto the
   * image toolkit.
   *
   * This is a temporary solution to keep patches reviewable. The __call()
   * method will be replaced in https://drupal.org/node/2110499 with a new
   * interface method ImageInterface::apply(). An image operation will be
   * performed as in the next example:
   * @code
   * $image = new Image($toolkit, $path);
   * $image->apply('scale', array('width' => 50, 'height' => 100));
   * @endcode
   * Also in https://drupal.org/node/2110499 operation arguments sent to toolkit
   * will be moved to a keyed array, unifying the interface of toolkit
   * operations.
   *
   * @todo Drop this in https://drupal.org/node/2110499 in favor of new apply().
   */
  public function __call($method, $arguments) {
    // @todo Temporary to avoid that legacy GD setResource(), getResource(),
    //  hasResource() methods moved to GD toolkit in #2103621, and setWidth(),
    //  setHeight() methods moved to ImageToolkitInterface in #2196067 get
    //  invoked from this class anyway through the magic __call. Will be
    //  removed through https://drupal.org/node/2110499, when
    //  call_user_func_array() will be replaced by
    //  $this->toolkit->apply($name, $this, $arguments).
    if (in_array($method, array('setResource', 'getResource', 'hasResource', 'setWidth', 'setHeight'))) {
      throw new \BadMethodCallException();
    }
    if (is_callable(array($this->toolkit, $method))) {
      // @todo In https://drupal.org/node/2110499, call_user_func_array() will
      //   be replaced by $this->toolkit->apply($name, $this, $arguments).
      array_unshift($arguments, $this);
      return call_user_func_array(array($this->toolkit, $method), $arguments);
    }
    throw new \BadMethodCallException();
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
