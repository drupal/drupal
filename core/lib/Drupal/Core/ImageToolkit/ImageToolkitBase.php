<?php

/**
 * @file
 * Contains \Drupal\Core\ImageToolkit\ImageToolkitBase.
 */

namespace Drupal\Core\ImageToolkit;

use Drupal\Core\Plugin\PluginBase;

abstract class ImageToolkitBase extends PluginBase implements ImageToolkitInterface {

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    return array();
  }

}
