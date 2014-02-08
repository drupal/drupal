<?php

/**
 * @file
 * Contains \Drupal\editor_test\EditorXssFilter\Insecure.
 */

namespace Drupal\editor_test\EditorXssFilter;

use Drupal\filter\FilterFormatInterface;
use Drupal\editor\EditorXssFilterInterface;

/**
 * Defines an insecure text editor XSS filter (for testing purposes).
 */
class Insecure implements EditorXssFilterInterface {

  /**
   * {@inheritdoc}
   */
  public static function filterXss($html, FilterFormatInterface $format, FilterFormatInterface $original_format = NULL) {
    // Don't apply any XSS filtering, just return the string we received.
    return $html;
  }

}
