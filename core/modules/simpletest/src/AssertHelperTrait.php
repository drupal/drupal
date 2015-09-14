<?php

/**
 * @file
 * Contains \Drupal\simpletest\AssertHelperTrait.
 */

namespace Drupal\simpletest;

use Drupal\Component\Utility\SafeStringInterface;

/**
 * Provides helper methods for assertions.
 */
trait AssertHelperTrait {

  /**
   * Casts SafeStringInterface objects into strings.
   *
   * @param string|array $value
   *   The value to act on.
   *
   * @return mixed
   *   The input value, with SafeStringInterface objects casted to string.
   */
  protected function castSafeStrings($value) {
    if ($value instanceof SafeStringInterface) {
      $value = (string) $value;
    }
    if (is_array($value)) {
      array_walk_recursive($value, function (&$item) {
        if ($item instanceof SafeStringInterface) {
          $item = (string) $item;
        }
      });
    }
    return $value;
  }

}
