<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Hidden.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a form element for an HTML 'hidden' input element.
 *
 * @see \Drupal\Core\Render\Element\Value
 *
 * @FormElement("hidden")
 */
class Hidden extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderHidden'),
      ),
      '#theme' => 'input__hidden',
    );
  }

  /**
   * Prepares a #type 'hidden' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #name, #value, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderHidden($element) {
    $element['#attributes']['type'] = 'hidden';
    Element::setAttributes($element, array('name', 'value'));

    return $element;
  }

}
