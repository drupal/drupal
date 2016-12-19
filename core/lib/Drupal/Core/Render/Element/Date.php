<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for date selection.
 *
 * Properties:
 * - #default_value: An array with the keys: 'year', 'month', and 'day'.
 *   Defaults to the current date if no value is supplied.
 * - #size: The size of the input element in characters.
 *
 * @code
 * $form['expiration'] = array(
 *   '#type' => 'date',
 *   '#title' => $this->t('Content expiration'),
 *   '#default_value' => array('year' => 2020, 'month' => 2, 'day' => 15,)
 * );
 * @endcode
 *
 * @FormElement("date")
 */
class Date extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#theme' => 'input__date',
      '#process' => [[$class, 'processDate']],
      '#pre_render' => [[$class, 'preRenderDate']],
      '#theme_wrappers' => ['form_element'],
      '#attributes' => ['type' => 'date'],
      '#date_date_format' => 'Y-m-d',
    ];
  }

  /**
   * Processes a date form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #attributes: An associative array containing:
   *     - type: The type of date field rendered.
   *   - #date_date_format: The date format used in PHP formats.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processDate(&$element, FormStateInterface $form_state, &$complete_form) {
    // Attach JS support for the date field, if we can determine which date
    // format should be used.
    if ($element['#attributes']['type'] == 'date' && !empty($element['#date_date_format'])) {
      $element['#attached']['library'][] = 'core/drupal.date';
      $element['#attributes']['data-drupal-date-format'] = [$element['#date_date_format']];
    }
    return $element;
  }

  /**
   * Adds form-specific attributes to a 'date' #type element.
   *
   * Supports HTML5 types of 'date', 'datetime', 'datetime-local', and 'time'.
   * Falls back to a plain textfield with JS datepicker support. Used as a
   * sub-element by the datetime element type.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #options, #description, #required,
   *   #attributes, #id, #name, #type, #min, #max, #step, #value, #size. The
   *   #name property will be sanitized before output. This is currently done by
   *   initializing Drupal\Core\Template\Attribute with all the attributes.
   *
   * @return array
   *   The $element with prepared variables ready for #theme 'input__date'.
   */
  public static function preRenderDate($element) {
    if (empty($element['#attributes']['type'])) {
      $element['#attributes']['type'] = 'date';
    }
    Element::setAttributes($element, array('id', 'name', 'type', 'min', 'max', 'step', 'value', 'size'));
    static::setAttributes($element, array('form-' . $element['#attributes']['type']));

    return $element;
  }

}
