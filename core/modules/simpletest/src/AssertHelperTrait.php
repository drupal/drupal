<?php

/**
 * @file
 * Contains \Drupal\simpletest\AssertHelperTrait.
 */

namespace Drupal\simpletest;

use Drupal\Component\Render\MarkupInterface;

/**
 * Provides helper methods for assertions.
 */
trait AssertHelperTrait {

  /**
   * Casts MarkupInterface objects into strings.
   *
   * @param string|array $value
   *   The value to act on.
   *
   * @return mixed
   *   The input value, with MarkupInterface objects casted to string.
   */
  protected function castSafeStrings($value) {
    if ($value instanceof MarkupInterface) {
      $value = (string) $value;
    }
    if (is_array($value)) {
      array_walk_recursive($value, function (&$item) {
        if ($item instanceof MarkupInterface) {
          $item = (string) $item;
        }
      });
    }
    return $value;
  }

}
