<?php

/**
 * @file
 * Contains \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface.
 */

namespace Drupal\Core\ImageToolkit;

/**
 * Defines an interface for image toolkit operation managers.
 */
interface ImageToolkitOperationManagerInterface {

  /**
   * Returns a toolkit operation plugin instance.
   *
   * @param \Drupal\Core\ImageToolkit\ImageToolkitInterface $toolkit
   *   The toolkit instance.
   * @param string $operation
   *   The operation (e.g. "crop").
   *
   * @return \Drupal\Core\ImageToolkit\ImageToolkitOperationInterface
   *   An instance of the requested toolkit operation plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   When no plugin is available.
   */
  public function getToolkitOperation(ImageToolkitInterface $toolkit, $operation);

}
