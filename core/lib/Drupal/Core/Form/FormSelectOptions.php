<?php

declare(strict_types=1);

namespace Drupal\Core\Form;

/**
 * Provides common functionality for form element options.
 *
 * @final
 */
final class FormSelectOptions {

  private function __construct() {}

  /**
   * Converts the options in a select element to a structured array for output.
   *
   * This function calls itself recursively to obtain the values for each
   * optgroup within the list of options and when the function encounters an
   * object with an 'options' property inside $element['#options'].
   *
   * @param array $element
   *   An associative array containing properties of the select element. See
   *   \Drupal\Core\Render\Element\Select for details, but note that the
   *   #empty_option and #empty_value properties are processed, and the
   *   #value property is set, before reaching this function.
   * @param array|null $choices
   *   (optional) Either an associative array of options in the same format as
   *   $element['#options'] above, or NULL. This parameter is only used
   *   internally and is not intended to be passed in to the initial function
   *   call.
   *
   * @return mixed[]
   *   A structured, possibly nested, array of options and optgroups for use in
   *   a select form element.
   *   - label: A translated string whose value is the text of a single HTML
   *     option element, or the label attribute for an optgroup.
   *   - options: Optional, array of options for an optgroup.
   *   - selected: A boolean that indicates whether the option is selected when
   *     rendered.
   *   - type: A string that defines the element type. The value can be 'option'
   *     or 'optgroup'.
   *   - value: A string that contains the value attribute for the option.
   */
  public static function process(array $element, $choices = NULL): array {
    if (!isset($choices)) {
      if (empty($element['#options'])) {
        return [];
      }
      $choices = $element['#options'];
      $sort_options = isset($element['#sort_options']) && $element['#sort_options'];
      $sort_start = $element['#sort_start'] ?? 0;
    }
    else {
      // We are within an option group.
      $sort_options = isset($choices['#sort_options']) && $choices['#sort_options'];
      $sort_start = $choices['#sort_start'] ?? 0;
      unset($choices['#sort_options']);
      unset($choices['#sort_start']);
    }

    // The function array_key_exists() accommodates the rare event where
    // $element['#value'] is NULL. isset() fails in this situation.
    $value_valid = array_key_exists('#value', $element);
    $value_is_array = $value_valid && is_array($element['#value']);
    // Check if the element is multiple select and no value has been selected.
    $empty_value = (empty($element['#value']) && !empty($element['#multiple']));
    $options = [];
    foreach ($choices as $key => $choice) {
      if (is_array($choice)) {
        $options[] = [
          'type' => 'optgroup',
          'label' => $key,
          'options' => static::process($element, $choice),
        ];
      }
      elseif (is_object($choice) && isset($choice->option)) {
        $options = array_merge($options, static::process($element, $choice->option));
      }
      else {
        $option = [];
        $key = (string) $key;
        $empty_choice = $empty_value && $key === '_none';
        if ($value_valid && ((!$value_is_array && (string) $element['#value'] === $key || ($value_is_array && in_array($key, $element['#value']))) || $empty_choice)) {
          $option['selected'] = TRUE;
        }
        else {
          $option['selected'] = FALSE;
        }
        $option['type'] = 'option';
        $option['value'] = $key;
        $option['label'] = $choice;
        $options[] = $option;
      }
    }

    if ($sort_options) {
      $unsorted = array_slice($options, 0, $sort_start);
      $sorted = array_slice($options, $sort_start);
      uasort($sorted, function ($a, $b) {
        return strcmp((string) $a['label'], (string) $b['label']);
      });
      $options = array_merge($unsorted, $sorted);
    }

    return $options;
  }

}
