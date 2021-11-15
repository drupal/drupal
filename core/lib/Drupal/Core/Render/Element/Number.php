<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Number as NumberUtility;

/**
 * Provides a form element for numeric input, with special numeric validation.
 *
 * Properties:
 * - #default_value: A valid floating point number.
 * - #min: Minimum value.
 * - #max: Maximum value.
 * - #step: Ensures that the number is an even multiple of step, offset by #min
 *   if specified. A #min of 1 and a #step of 2 would allow values of 1, 3, 5,
 *   etc.
 *
 * Usage example:
 * @code
 * $form['quantity'] = array(
 *   '#type' => 'number',
 *   '#title' => $this->t('Quantity'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Range
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("number")
 */
class Number extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#step' => 1,
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateNumber'],
      ],
      '#pre_render' => [
        [$class, 'preRenderNumber'],
      ],
      '#theme' => 'input__number',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Form element validation handler for #type 'number'.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    if ($value === '') {
      return;
    }

    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];

    // Ensure the input is numeric.
    if (!is_numeric($value)) {
      $form_state->setError($element, t('%name must be a number.', ['%name' => $name]));
      return;
    }

    // Ensure that the input is greater than the #min property, if set.
    if (isset($element['#min']) && $value < $element['#min']) {
      $form_state->setError($element, t('%name must be higher than or equal to %min.', ['%name' => $name, '%min' => $element['#min']]));
    }

    // Ensure that the input is less than the #max property, if set.
    if (isset($element['#max']) && $value > $element['#max']) {
      $form_state->setError($element, t('%name must be lower than or equal to %max.', ['%name' => $name, '%max' => $element['#max']]));
    }

    if (isset($element['#step']) && strtolower($element['#step']) != 'any') {
      // Check that the input is an allowed multiple of #step (offset by #min if
      // #min is set).
      $offset = $element['#min'] ?? 0.0;

      if (!NumberUtility::validStep($value, $element['#step'], $offset)) {
        $form_state->setError($element, t('%name is not a valid number.', ['%name' => $name]));
      }
    }
  }

  /**
   * Prepares a #type 'number' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #placeholder,
   *   #required, #attributes, #step, #size.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderNumber($element) {
    $element['#attributes']['type'] = 'number';
    Element::setAttributes($element, ['id', 'name', 'value', 'step', 'min', 'max', 'placeholder', 'size']);
    static::setAttributes($element, ['form-number']);

    return $element;
  }

}
