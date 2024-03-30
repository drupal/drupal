<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for date selection.
 *
 * Properties:
 * - #default_value: A string for the default date in 'Y-m-d' format.
 * - #size: The size of the input element in characters.
 *
 * @code
 * $form['expiration'] = [
 *   '#type' => 'date',
 *   '#title' => $this->t('Content expiration'),
 *   '#default_value' => '2020-02-05',
 * ];
 * @endcode
 */
#[FormElement('date')]
class Date extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#theme' => 'input__date',
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [[$class, 'preRenderDate']],
      '#theme_wrappers' => ['form_element'],
      '#attributes' => ['type' => 'date'],
      '#date_date_format' => 'Y-m-d',
    ];
  }

  /**
   * Adds form-specific attributes to a 'date' #type element.
   *
   * Supports HTML5 types of 'date', 'datetime', 'datetime-local', and 'time'.
   * Falls back to a plain textfield. Used as a sub-element by the datetime
   * element type.
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
    Element::setAttributes($element, ['id', 'name', 'type', 'min', 'max', 'step', 'value', 'size']);
    static::setAttributes($element, ['form-' . $element['#attributes']['type']]);

    return $element;
  }

}
