<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormHelper.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Render\Element;

/**
 * Provides helpers to operate on forms.
 *
 * @ingroup form_api
 */
class FormHelper {

  /**
   * Rewrite #states selectors.
   *
   * @param array $elements
   *   A renderable array element having a #states property.
   * @param string $search
   *   A partial or entire jQuery selector string to replace in #states.
   * @param string $replace
   *   The string to replace all instances of $search with.
   *
   * @see drupal_process_states()
   */
  public static function rewriteStatesSelector(array &$elements, $search, $replace) {
    if (!empty($elements['#states'])) {
      foreach ($elements['#states'] as $state => $ids) {
        static::processStatesArray($elements['#states'][$state], $search, $replace);
      }
    }
    foreach (Element::children($elements) as $key) {
      static::rewriteStatesSelector($elements[$key], $search, $replace);
    }
  }

  /**
   * Helper function for self::rewriteStatesSelector().
   *
   * @param array $conditions
   *   States conditions array.
   * @param string $search
   *   A partial or entire jQuery selector string to replace in #states.
   * @param string $replace
   *   The string to replace all instances of $search with.
   */
  protected static function processStatesArray(array &$conditions, $search, $replace) {
    // Retrieve the keys to make it easy to rename a key without changing the
    // order of an array.
    $keys = array_keys($conditions);
    $update_keys = FALSE;
    foreach ($conditions as $id => $values) {
      if (strpos($id, $search) !== FALSE) {
        $update_keys = TRUE;
        $new_id = str_replace($search, $replace, $id);
        // Replace the key and keep the array in the same order.
        $index = array_search($id, $keys, TRUE);
        $keys[$index] = $new_id;
      }
      elseif (is_array($values)) {
        static::processStatesArray($conditions[$id], $search, $replace);
      }
    }
    // Updates the states conditions keys if necessary.
    if ($update_keys) {
      $conditions = array_combine($keys, array_values($conditions));
    }
  }

}
