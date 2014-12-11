<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Radio.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a form element for a single radio button.
 *
 * @see \Drupal\Core\Render\Element\Radios
 *
 * @FormElement("radio")
 */
class Radio extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#default_value' => NULL,
      '#process' => array(
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderRadio'),
      ),
      '#theme' => 'input__radio',
      '#theme_wrappers' => array('form_element'),
      '#title_display' => 'after',
    );
  }

  /**
   * Prepares a #type 'radio' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #required, #return_value, #value, #attributes, #title,
   *   #description.
   *
   * Note: The input "name" attribute needs to be sanitized before output, which
   *       is currently done by initializing Drupal\Core\Template\Attribute with
   *       all the attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderRadio($element) {
    $element['#attributes']['type'] = 'radio';
    Element::setAttributes($element, array('id', 'name', '#return_value' => 'value'));

    if (isset($element['#return_value']) && $element['#value'] !== FALSE && $element['#value'] == $element['#return_value']) {
      $element['#attributes']['checked'] = 'checked';
    }
    static::setAttributes($element, array('form-radio'));

    return $element;
  }

}
