<?php

namespace Drupal\image_test\Plugin\ImageToolkit;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkit;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Test toolkit for image manipulation within Drupal.
 */
#[ImageToolkit(
  id: "broken",
  title: new TranslatableMarkup("A dummy toolkit that is broken"),
)]
class BrokenToolkit extends TestToolkit {

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return FALSE;
  }

}
