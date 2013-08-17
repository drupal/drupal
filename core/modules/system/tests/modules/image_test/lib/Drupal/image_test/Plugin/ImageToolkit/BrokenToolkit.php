<?php

/**
 * @file
 * Contains \Drupal\image_test\Plugin\ImageToolkit\BrokenToolkit.
 */

namespace Drupal\image_test\Plugin\ImageToolkit;

use Drupal\system\Annotation\ImageToolkit;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a Test toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "broken",
 *   title = @Translation("A dummy toolkit that is broken")
 * )
 */
class BrokenToolkit extends TestToolkit {

  /**
   * Implements \Drupal\system\Plugin\ImageToolkitInterface::isAvailable().
   */
  public static function isAvailable() {
    return FALSE;
  }
}
