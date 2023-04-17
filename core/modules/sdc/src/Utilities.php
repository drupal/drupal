<?php

namespace Drupal\sdc;

/**
 * Shared utilities for SDC.
 *
 * @internal
 */
final class Utilities {

  /**
   * This class should not be instantiated.
   */
  private function __construct() {
  }

  /**
   * Checks if a candidate is a render array.
   *
   * @param mixed $candidate
   *   The candidate.
   *
   * @return bool
   *   TRUE if it's a render array. FALSE otherwise.
   *
   * @todo Move this to the \Drupal\Core\Render\Element class.
   * @see https://www.drupal.org/i/3352858
   */
  public static function isRenderArray($candidate): bool {
    if (!is_array($candidate)) {
      return FALSE;
    }
    if (empty($candidate)) {
      return FALSE;
    }
    foreach ($candidate as $key => $value) {
      if (!is_int($key) && $key !== '' && $key[0] === '#') {
        continue;
      }
      if (!is_array($value)) {
        return FALSE;
      }
      if (!static::isRenderArray($value)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
