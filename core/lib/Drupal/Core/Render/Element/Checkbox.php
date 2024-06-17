<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for a single checkbox.
 *
 * Properties:
 * - #return_value: The value to return when the checkbox is checked.
 *
 * Usage example:
 * @code
 * $form['copy'] = [
 *   '#type' => 'checkbox',
 *   '#title' => $this->t('Send me a copy'),
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 */
#[FormElement('checkbox')]
class Checkbox extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#return_value' => 1,
      '#process' => [
        [$class, 'processCheckbox'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCheckbox'],
        [$class, 'preRenderGroup'],
      ],
      '#theme' => 'input__checkbox',
      '#theme_wrappers' => ['form_element'],
      '#title_display' => 'after',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      // Use #default_value as the default value of a checkbox, except change
      // NULL to 0, because FormBuilder::handleInputElement() would otherwise
      // replace NULL with empty string, but an empty string is a potentially
      // valid value for a checked checkbox.
      return $element['#default_value'] ?? 0;
    }
    else {
      // Checked checkboxes are submitted with a value (possibly '0' or ''):
      // http://www.w3.org/TR/html401/interact/forms.html#successful-controls.
      // For checked checkboxes, browsers submit the string version of
      // #return_value, but we return the original #return_value. For unchecked
      // checkboxes, browsers submit nothing at all, but
      // FormBuilder::handleInputElement() detects this, and calls this
      // function with $input=NULL. Returning NULL from a value callback means
      // to use the default value, which is not what is wanted when an unchecked
      // checkbox is submitted, so we use integer 0 as the value indicating an
      // unchecked checkbox. Therefore, modules must not use integer 0 as a
      // #return_value, as doing so results in the checkbox always being treated
      // as unchecked. The string '0' is allowed for #return_value. The most
      // common use-case for setting #return_value to either 0 or '0' is for the
      // first option within a 0-indexed array of checkboxes, and for this,
      // \Drupal\Core\Render\Element\Checkboxes::processCheckboxes() uses the
      // string rather than the integer.
      return isset($input) ? $element['#return_value'] : 0;
    }
  }

  /**
   * Prepares a #type 'checkbox' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #return_value, #description, #required,
   *   #attributes, #checked.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderCheckbox($element) {
    $element['#attributes']['type'] = 'checkbox';
    Element::setAttributes($element, ['id', 'name', '#return_value' => 'value']);

    // Unchecked checkbox has #value of integer 0.
    if (!empty($element['#checked'])) {
      $element['#attributes']['checked'] = 'checked';
    }
    static::setAttributes($element, ['form-checkbox']);

    return $element;
  }

  /**
   * Sets the #checked property of a checkbox element.
   */
  public static function processCheckbox(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    $return_value = $element['#return_value'];
    // On form submission, the #value of an available and enabled checked
    // checkbox is #return_value, and the #value of an available and enabled
    // unchecked checkbox is integer 0. On not submitted forms, and for
    // checkboxes with #access=FALSE or #disabled=TRUE, the #value is
    // #default_value (integer 0 if #default_value is NULL). Most of the time,
    // a string comparison of #value and #return_value is sufficient for
    // determining the "checked" state, but a value of TRUE always means checked
    // (even if #return_value is 'foo'), and a value of FALSE or integer 0
    // always means unchecked (even if #return_value is '' or '0').
    if ($value === TRUE || $value === FALSE || $value === 0) {
      $element['#checked'] = (bool) $value;
    }
    else {
      // Compare as strings, so that 15 is not considered equal to '15foo', but
      // 1 is considered equal to '1'. This cast does not imply that either
      // #value or #return_value is expected to be a string.
      $element['#checked'] = ((string) $value === (string) $return_value);
    }
    return $element;
  }

}
