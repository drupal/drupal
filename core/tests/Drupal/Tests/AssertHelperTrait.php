<?php

namespace Drupal\Tests;

use Drupal\Component\Render\MarkupInterface;

@trigger_error(__NAMESPACE__ . '\AssertHelperTrait is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3123638', E_USER_DEPRECATED);

/**
 * Provides helper methods for assertions.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3123638
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
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no
   *   replacement, just use assertEquals in tests.
   *
   * @see https://www.drupal.org/node/3123638
   */
  protected static function castSafeStrings($value) {
    @trigger_error('AssertHelperTrait::castSafeStrings() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no replacement; assertEquals() will automatically cast MarkupInterface to strings when needed. See https://www.drupal.org/node/3123638', E_USER_DEPRECATED);
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
