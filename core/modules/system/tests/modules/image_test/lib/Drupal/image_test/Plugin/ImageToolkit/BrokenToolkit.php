<?php

/**
 * @file
 * Contains \Drupal\image_test\Plugin\ImageToolkit\BrokenToolkit.
 */

namespace Drupal\image_test\Plugin\ImageToolkit;

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
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return FALSE;
  }
}
