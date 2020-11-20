<?php

namespace Drupal\Core\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Element;

/**
 * Provides helpers to operate on forms.
 *
 * @ingroup form_api
 */
class FormHelper {

  /**
   * Rewrites #states selectors in a render element.
   *
   * When a structure of elements is being altered, their HTML selectors may
   * change. In such cases calling this method will check if there are any
   * states in element and its children, and rewrite selectors in those states.
   *
   * @param array $elements
   *   A render array element having a #states property.
   * @param string $search
   *   A partial or entire jQuery selector string to replace in #states.
   * @param string $replace
   *   The string to replace all instances of $search with.
   *
   * @see self::processStates()
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
   * Helps recursively rewrite #states selectors.
   *
   * Not to be confused with self::processStates(), which just prepares states
   * for rendering.
   *
   * @param array $conditions
   *   States conditions array.
   * @param string $search
   *   A partial or entire jQuery selector string to replace in #states.
   * @param string $replace
   *   The string to replace all instances of $search with.
   *
   * @see self::rewriteStatesSelector()
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

  /**
   * Adds JavaScript to change the state of an element based on another element.
   *
   * A "state" means a certain property of a DOM element, such as "visible" or
   * "checked", which depends on a state or value of another element on the
   * page. In general, states are HTML attributes and DOM element properties,
   * which are applied initially, when page is loaded, depending on elements'
   * default values, and then may change due to user interaction.
   *
   * Since states are driven by JavaScript only, it is important to understand
   * that all states are applied on presentation only, none of the states force
   * any server-side logic, and that they will not be applied for site visitors
   * without JavaScript support. All modules implementing states have to make
   * sure that the intended logic also works without JavaScript being enabled.
   *
   * #states is an associative array in the form of:
   * @code
   * [
   *   STATE1 => CONDITIONS_ARRAY1,
   *   STATE2 => CONDITIONS_ARRAY2,
   *   ...
   * ]
   * @endcode
   * Each key is the name of a state to apply to the element, such as 'visible'.
   * Each value is a list of conditions that denote when the state should be
   * applied.
   *
   * Multiple different states may be specified to act on complex conditions:
   * @code
   * [
   *   'visible' => CONDITIONS,
   *   'checked' => OTHER_CONDITIONS,
   * ]
   * @endcode
   *
   * Every condition is a key/value pair, whose key is a jQuery selector that
   * denotes another element on the page, and whose value is an array of
   * conditions, which must bet met on that element:
   * @code
   * [
   *   'visible' => [
   *     JQUERY_SELECTOR => REMOTE_CONDITIONS,
   *     JQUERY_SELECTOR => REMOTE_CONDITIONS,
   *     ...
   *   ],
   * ]
   * @endcode
   * All conditions must be met for the state to be applied.
   *
   * Each remote condition is a key/value pair specifying conditions on the
   * other element that need to be met to apply the state to the element:
   * @code
   * [
   *   'visible' => [
   *     ':input[name="remote_checkbox"]' => ['checked' => TRUE],
   *   ],
   * ]
   * @endcode
   *
   * For example, to show a textfield only when a checkbox is checked:
   * @code
   * $form['toggle_me'] = [
   *   '#type' => 'checkbox',
   *   '#title' => t('Tick this box to type'),
   * ];
   * $form['settings'] = [
   *   '#type' => 'textfield',
   *   '#states' => [
   *     // Only show this field when the 'toggle_me' checkbox is enabled.
   *     'visible' => [
   *       ':input[name="toggle_me"]' => ['checked' => TRUE],
   *     ],
   *   ],
   * ];
   * @endcode
   *
   * The following states may be applied to an element:
   * - enabled
   * - disabled
   * - required
   * - optional
   * - visible
   * - invisible
   * - checked
   * - unchecked
   * - expanded
   * - collapsed
   *
   * The following states may be used in remote conditions:
   * - empty
   * - filled
   * - checked
   * - unchecked
   * - expanded
   * - collapsed
   * - value
   *
   * The following states exist for both elements and remote conditions, but are
   * not fully implemented and may not change anything on the element:
   * - relevant
   * - irrelevant
   * - valid
   * - invalid
   * - touched
   * - untouched
   * - readwrite
   * - readonly
   *
   * When referencing select lists and radio buttons in remote conditions, a
   * 'value' condition must be used:
   * @code
   *   '#states' => [
   *     // Show the settings if 'bar' has been selected for 'foo'.
   *     'visible' => [
   *       ':input[name="foo"]' => ['value' => 'bar'],
   *     ],
   *   ],
   * @endcode
   *
   * @param array $elements
   *   A render array element having a #states property as described above.
   *
   * @see \Drupal\form_test\Form\JavascriptStatesForm
   * @see \Drupal\FunctionalJavascriptTests\Core\Form\JavascriptStatesTest
   */
  public static function processStates(array &$elements) {
    $elements['#attached']['library'][] = 'core/drupal.states';
    // Elements of '#type' => 'item' are not actual form input elements, but we
    // still want to be able to show/hide them. Since there's no actual HTML
    // input element available, setting #attributes does not make sense, but a
    // wrapper is available, so setting #wrapper_attributes makes it work.
    $key = ($elements['#type'] == 'item') ? '#wrapper_attributes' : '#attributes';
    $elements[$key]['data-drupal-states'] = Json::encode($elements['#states']);
  }

}
