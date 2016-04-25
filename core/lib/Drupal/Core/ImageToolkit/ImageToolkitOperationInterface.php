<?php

namespace Drupal\Core\ImageToolkit;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for image toolkit operations.
 *
 * An image toolkit operation plugin provides a self-contained image
 * manipulation routine, for a specific image toolkit. Examples of image
 * toolkit operations are scaling, cropping, rotating, etc.
 *
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkitOperation
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationBase
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationManager
 * @see plugin_api
 */
interface ImageToolkitOperationInterface extends PluginInspectionInterface {

  /**
   * Applies a toolkit specific operation to an image.
   *
   * @param array $arguments
   *   An associative array of data to be used by the toolkit operation.
   *
   * @return bool
   *   TRUE if the operation was performed successfully, FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   If one or more of the arguments are not valid.
   */
  public function apply(array $arguments);

}
