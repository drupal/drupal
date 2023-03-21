<?php

namespace Drupal\Component\Utility;

/**
 * Provides helpers to perform operations on nested arrays and array keys of variable depth.
 *
 * @ingroup utility
 */
class NestedArray {

  /**
   * Retrieves a value from a nested array with variable depth.
   *
   * This helper function should be used when the depth of the array element
   * being retrieved may vary (that is, the number of parent keys is variable).
   * It is primarily used for form structures and renderable arrays.
   *
   * Without this helper function the only way to get a nested array value with
   * variable depth in one line would be using eval(), which should be avoided:
   * @code
   * // Do not do this! Avoid eval().
   * // May also throw a PHP notice, if the variable array keys do not exist.
   * eval('$value = $array[\'' . implode("']['", $parents) . "'];");
   * @endcode
   *
   * Instead, use this helper function:
   * @code
   * $value = NestedArray::getValue($form, $parents);
   * @endcode
   *
   * A return value of NULL is ambiguous, and can mean either that the requested
   * key does not exist, or that the actual value is NULL. If it is required to
   * know whether the nested array key actually exists, pass a third argument
   * that is altered by reference:
   * @code
   * $key_exists = NULL;
   * $value = NestedArray::getValue($form, $parents, $key_exists);
   * if ($key_exists) {
   *   // Do something with $value.
   * }
   * @endcode
   *
   * However if the number of array parent keys is static, the value should
   * always be retrieved directly rather than calling this function.
   * For instance:
   * @code
   * $value = $form['signature_settings']['signature'];
   * @endcode
   *
   * @param array $array
   *   The array from which to get the value.
   * @param array $parents
   *   An array of parent keys of the value, starting with the outermost key.
   * @param bool $key_exists
   *   (optional) If given, an already defined variable that is altered by
   *   reference.
   *
   * @return mixed
   *   The requested nested value. Possibly NULL if the value is NULL or not all
   *   nested parent keys exist. $key_exists is altered by reference and is a
   *   Boolean that indicates whether all nested parent keys exist (TRUE) or not
   *   (FALSE). This allows to distinguish between the two possibilities when
   *   NULL is returned.
   *
   * @see NestedArray::setValue()
   * @see NestedArray::unsetValue()
   */
  public static function &getValue(array &$array, array $parents, &$key_exists = NULL) {
    $ref = &$array;
    foreach ($parents as $parent) {
      if (is_array($ref) && \array_key_exists($parent, $ref)) {
        $ref = &$ref[$parent];
      }
      else {
        $key_exists = FALSE;
        $null = NULL;
        return $null;
      }
    }
    $key_exists = TRUE;
    return $ref;
  }

  /**
   * Sets a value in a nested array with variable depth.
   *
   * This helper function should be used when the depth of the array element you
   * are changing may vary (that is, the number of parent keys is variable). It
   * is primarily used for form structures and renderable arrays.
   *
   * Example:
   * @code
   * // Assume you have a 'signature' element somewhere in a form. It might be:
   * $form['signature_settings']['signature'] = array(
   *   '#type' => 'text_format',
   *   '#title' => t('Signature'),
   * );
   * // Or, it might be further nested:
   * $form['signature_settings']['user']['signature'] = array(
   *   '#type' => 'text_format',
   *   '#title' => t('Signature'),
   * );
   * @endcode
   *
   * To deal with the situation, the code needs to figure out the route to the
   * element, given an array of parents that is either
   * @code array('signature_settings', 'signature') @endcode
   * in the first case or
   * @code array('signature_settings', 'user', 'signature') @endcode
   * in the second case.
   *
   * Without this helper function the only way to set the signature element in
   * one line would be using eval(), which should be avoided:
   * @code
   * // Do not do this! Avoid eval().
   * eval('$form[\'' . implode("']['", $parents) . '\'] = $element;');
   * @endcode
   *
   * Instead, use this helper function:
   * @code
   * NestedArray::setValue($form, $parents, $element);
   * @endcode
   *
   * However if the number of array parent keys is static, the value should
   * always be set directly rather than calling this function. For instance,
   * for the first example we could just do:
   * @code
   * $form['signature_settings']['signature'] = $element;
   * @endcode
   *
   * @param array $array
   *   A reference to the array to modify.
   * @param array $parents
   *   An array of parent keys, starting with the outermost key.
   * @param mixed $value
   *   The value to set.
   * @param bool $force
   *   (optional) If TRUE, the value is forced into the structure even if it
   *   requires the deletion of an already existing non-array parent value. If
   *   FALSE, PHP throws an error if trying to add into a value that is not an
   *   array. Defaults to FALSE.
   *
   * @see NestedArray::unsetValue()
   * @see NestedArray::getValue()
   */
  public static function setValue(array &$array, array $parents, $value, $force = FALSE) {
    $ref = &$array;
    foreach ($parents as $parent) {
      // PHP auto-creates container arrays and NULL entries without error if $ref
      // is NULL, but throws an error if $ref is set, but not an array.
      if ($force && isset($ref) && !is_array($ref)) {
        $ref = [];
      }
      $ref = &$ref[$parent];
    }
    $ref = $value;
  }

  /**
   * Unsets a value in a nested array with variable depth.
   *
   * This helper function should be used when the depth of the array element you
   * are changing may vary (that is, the number of parent keys is variable). It
   * is primarily used for form structures and renderable arrays.
   *
   * Example:
   * @code
   * // Assume you have a 'signature' element somewhere in a form. It might be:
   * $form['signature_settings']['signature'] = array(
   *   '#type' => 'text_format',
   *   '#title' => t('Signature'),
   * );
   * // Or, it might be further nested:
   * $form['signature_settings']['user']['signature'] = array(
   *   '#type' => 'text_format',
   *   '#title' => t('Signature'),
   * );
   * @endcode
   *
   * To deal with the situation, the code needs to figure out the route to the
   * element, given an array of parents that is either
   * @code array('signature_settings', 'signature') @endcode
   * in the first case or
   * @code array('signature_settings', 'user', 'signature') @endcode
   * in the second case.
   *
   * Without this helper function the only way to unset the signature element in
   * one line would be using eval(), which should be avoided:
   * @code
   * // Do not do this! Avoid eval().
   * eval('unset($form[\'' . implode("']['", $parents) . '\']);');
   * @endcode
   *
   * Instead, use this helper function:
   * @code
   * NestedArray::unsetValue($form, $parents, $element);
   * @endcode
   *
   * However if the number of array parent keys is static, the value should
   * always be set directly rather than calling this function. For instance, for
   * the first example we could just do:
   * @code
   * unset($form['signature_settings']['signature']);
   * @endcode
   *
   * @param array $array
   *   A reference to the array to modify.
   * @param array $parents
   *   An array of parent keys, starting with the outermost key and including
   *   the key to be unset.
   * @param bool $key_existed
   *   (optional) If given, an already defined variable that is altered by
   *   reference.
   *
   * @see NestedArray::setValue()
   * @see NestedArray::getValue()
   */
  public static function unsetValue(array &$array, array $parents, &$key_existed = NULL) {
    $unset_key = array_pop($parents);
    $ref = &self::getValue($array, $parents, $key_existed);
    if ($key_existed && is_array($ref) && \array_key_exists($unset_key, $ref)) {
      $key_existed = TRUE;
      unset($ref[$unset_key]);
    }
    else {
      $key_existed = FALSE;
    }
  }

  /**
   * Determines whether a nested array contains the requested keys.
   *
   * This helper function should be used when the depth of the array element to
   * be checked may vary (that is, the number of parent keys is variable). See
   * NestedArray::setValue() for details. It is primarily used for form
   * structures and renderable arrays.
   *
   * If it is required to also get the value of the checked nested key, use
   * NestedArray::getValue() instead.
   *
   * If the number of array parent keys is static, this helper function is
   * unnecessary and the following code can be used instead:
   * @code
   * $value_exists = isset($form['signature_settings']['signature']);
   * $key_exists = array_key_exists('signature', $form['signature_settings']);
   * @endcode
   *
   * @param array $array
   *   The array with the value to check for.
   * @param array $parents
   *   An array of parent keys of the value, starting with the outermost key.
   *
   * @return bool
   *   TRUE if all the parent keys exist, FALSE otherwise.
   *
   * @see NestedArray::getValue()
   */
  public static function keyExists(array $array, array $parents) {
    // Although this function is similar to PHP's array_key_exists(), its
    // arguments should be consistent with getValue().
    $key_exists = NULL;
    self::getValue($array, $parents, $key_exists);
    return $key_exists;
  }

  /**
   * Merges multiple arrays, recursively, and returns the merged array.
   *
   * This function is similar to PHP's array_merge_recursive() function, but it
   * handles non-array values differently. When merging values that are not both
   * arrays, the latter value replaces the former rather than merging with it.
   *
   * Example:
   * @code
   * $link_options_1 = array('fragment' => 'x', 'attributes' => array('title' => t('X'), 'class' => array('a', 'b')));
   * $link_options_2 = array('fragment' => 'y', 'attributes' => array('title' => t('Y'), 'class' => array('c', 'd')));
   *
   * // This results in array('fragment' => array('x', 'y'), 'attributes' => array('title' => array(t('X'), t('Y')), 'class' => array('a', 'b', 'c', 'd'))).
   * $incorrect = array_merge_recursive($link_options_1, $link_options_2);
   *
   * // This results in array('fragment' => 'y', 'attributes' => array('title' => t('Y'), 'class' => array('a', 'b', 'c', 'd'))).
   * $correct = NestedArray::mergeDeep($link_options_1, $link_options_2);
   * @endcode
   *
   * @param array ...
   *   Arrays to merge.
   *
   * @return array
   *   The merged array.
   *
   * @see NestedArray::mergeDeepArray()
   */
  public static function mergeDeep() {
    return self::mergeDeepArray(func_get_args());
  }

  /**
   * Merges multiple arrays, recursively, and returns the merged array.
   *
   * This function is equivalent to NestedArray::mergeDeep(), except the
   * input arrays are passed as a single array parameter rather than a variable
   * parameter list.
   *
   * The following are equivalent:
   * - NestedArray::mergeDeep($a, $b);
   * - NestedArray::mergeDeepArray(array($a, $b));
   *
   * The following are also equivalent:
   * - call_user_func_array('NestedArray::mergeDeep', $arrays_to_merge);
   * - NestedArray::mergeDeepArray($arrays_to_merge);
   *
   * @param array $arrays
   *   An arrays of arrays to merge.
   * @param bool $preserve_integer_keys
   *   (optional) If given, integer keys will be preserved and merged instead of
   *   appended. Defaults to FALSE.
   *
   * @return array
   *   The merged array.
   *
   * @see NestedArray::mergeDeep()
   */
  public static function mergeDeepArray(array $arrays, $preserve_integer_keys = FALSE) {
    $result = [];
    foreach ($arrays as $array) {
      foreach ($array as $key => $value) {
        // Renumber integer keys as array_merge_recursive() does unless
        // $preserve_integer_keys is set to TRUE. Note that PHP automatically
        // converts array keys that are integer strings (e.g., '1') to integers.
        if (is_int($key) && !$preserve_integer_keys) {
          $result[] = $value;
        }
        // Recurse when both values are arrays.
        elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
          $result[$key] = self::mergeDeepArray([$result[$key], $value], $preserve_integer_keys);
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $result[$key] = $value;
        }
      }
    }
    return $result;
  }

  /**
   * Filters a nested array recursively.
   *
   * @param array $array
   *   The filtered nested array.
   * @param callable|null $callable
   *   The callable to apply for filtering.
   *
   * @return array
   *   The filtered array.
   */
  public static function filter(array $array, callable $callable = NULL) {
    $array = is_callable($callable) ? array_filter($array, $callable) : array_filter($array);
    foreach ($array as &$element) {
      if (is_array($element)) {
        $element = static::filter($element, $callable);
      }
    }

    return $array;
  }

}
