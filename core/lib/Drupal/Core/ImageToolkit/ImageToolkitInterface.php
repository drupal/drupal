<?php

/**
 * @file
 * Contains \Drupal\Core\ImageToolkit\ImageToolkitInterface.
 */

namespace Drupal\Core\ImageToolkit;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * @defgroup image Image toolkits
 * @{
 * Functions for image file manipulations.
 *
 * Drupal's image toolkits provide an abstraction layer for common image file
 * manipulations like scaling, cropping, and rotating. The abstraction frees
 * module authors from the need to support multiple image libraries, and it
 * allows site administrators to choose the library that's best for them.
 *
 * PHP includes the GD library by default so a GD toolkit is installed with
 * Drupal. Other toolkits like ImageMagick are available from contrib modules.
 * GD works well for small images, but using it with larger files may cause PHP
 * to run out of memory. In contrast the ImageMagick library does not suffer
 * from this problem, but it requires the ISP to have installed additional
 * software.
 *
 * Image toolkits are discovered using the Plugin system using
 * \Drupal\Core\ImageToolkit\ImageToolkitManager. The toolkit must then be
 * enabled using the admin/config/media/image-toolkit form.
 *
 * Only one toolkit may be selected at a time. If a module author wishes to call
 * a specific toolkit they can check that it is installed by calling
 * \Drupal\Core\ImageToolkit\ImageToolkitManager::getAvailableToolkits(), and
 * then calling its functions directly.
 */

/**
 * Defines an interface for image toolkits.
 *
 * An image toolkit provides common image file manipulations like scaling,
 * cropping, and rotating.
 *
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkit
 * @see \Drupal\Core\ImageToolkit\ImageToolkitBase
 * @see \Drupal\Core\ImageToolkit\ImageToolkitManager
 * @see plugin_api
 */
interface ImageToolkitInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, PluginFormInterface {

  /**
   * Sets the image object that this toolkit instance is tied to.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image that this toolkit instance will be tied to.
   *
   * @throws \BadMethodCallException
   *   When called twice.
   */
  public function setImage(ImageInterface $image);

  /**
   * Gets the image object that this toolkit instance is tied to.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The image object that this toolkit instance is tied to.
   */
  public function getImage();

  /**
   * Checks if the image is valid.
   *
   * @return bool
   *   TRUE if the image toolkit is currently handling a valid image, FALSE
   *   otherwise.
   */
  public function isValid();

  /**
   * Writes an image resource to a destination file.
   *
   * @param string $destination
   *   A string file URI or path where the image should be saved.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function save($destination);

  /**
   * Determines if a file contains a valid image.
   *
   * Drupal supports GIF, JPG and PNG file formats when used with the GD
   * toolkit, and may support others, depending on which toolkits are
   * installed.
   *
   * @return bool
   *   TRUE if the file could be found and is an image, FALSE otherwise.
   */
  public function parseFile();

  /**
   * Returns the height of the image.
   *
   * @return int|null
   *   The height of the image, or NULL if the image is invalid.
   */
  public function getHeight();

  /**
   * Returns the width of the image.
   *
   * @return int|null
   *   The width of the image, or NULL if the image is invalid.
   */
  public function getWidth();

  /**
   * Returns the MIME type of the image file.
   *
   * @return string
   *   The MIME type of the image file, or an empty string if the image is
   *   invalid.
   */
  public function getMimeType();

  /**
   * Gets toolkit requirements in a format suitable for hook_requirements().
   *
   * @return array
   *   An associative requirements array as is returned by hook_requirements().
   *   If the toolkit claims no requirements to the system, returns an empty
   *   array. The array can have arbitrary keys and they do not have to be
   *   prefixed by e.g. the module name or toolkit ID, as the system will make
   *   the keys globally unique.
   *
   * @see hook_requirements()
   */
  public function getRequirements();

  /**
   * Verifies that the Image Toolkit is set up correctly.
   *
   * @return bool
   *   TRUE if the toolkit is available on this machine, FALSE otherwise.
   */
  public static function isAvailable();

  /**
   * Returns a list of image file extensions supported by the toolkit.
   *
   * @return array
   *   An array of supported image file extensions (e.g. png/jpeg/gif).
   */
  public static function getSupportedExtensions();

  /**
   * Applies a toolkit operation to an image.
   *
   * @param string $operation
   *   The toolkit operation to be processed.
   * @param array $arguments
   *   An associative array of arguments to be passed to the toolkit
   *   operation, e.g. array('width' => 50, 'height' => 100,
   *   'upscale' => TRUE).
   *
   * @return bool
   *   TRUE if the operation was performed successfully, FALSE otherwise.
   */
  public function apply($operation, array $arguments = array());

}
