<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Date.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a form element for date selection.
 *
 * The #default_value will be today's date if no value is supplied. The format
 * for the #default_value and the #return_value is an array with three elements
 * with the keys: 'year', month', and 'day'. For example,
 * array('year' => 2007, 'month' => 2, 'day' => 15)
 *
 * @FormElement("date")
 */
class Date extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#theme' => 'input__date',
      '#pre_render' => array(
        array($class, 'preRenderDate'),
      ),
      '#theme_wrappers' => array('form_element'),
    );
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
   *   #attributes, #id, #name, #type, #min, #max, #step, #value, #size.
   *
   * Note: The input "name" attribute needs to be sanitized before output, which
   *       is currently done by initializing Drupal\Core\Template\Attribute with
   *       all the attributes.
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
