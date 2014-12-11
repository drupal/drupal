<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Color.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Color as ColorUtility;

/**
 * Provides a form element for choosing a color.
 *
 * @FormElement("color")
 */
class Color extends FormElement {

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
      '#element_validate' => array(
        array($class, 'validateColor'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderColor'),
      ),
      '#theme' => 'input__color',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * Form element validation handler for #type 'color'.
   */
  public static function validateColor(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);

    // Default to black if no value is given.
    // @see http://www.w3.org/TR/html5/number-state.html#color-state
    if ($value === '') {
      $form_state->setValueForElement($element, '#000000');
    }
    else {
      // Try to parse the value and normalize it.
      try {
        $form_state->setValueForElement($element, ColorUtility::rgbToHex(ColorUtility::hexToRgb($value)));
      }
      catch (\InvalidArgumentException $e) {
        $form_state->setError($element, t('%name must be a valid color.', array('%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'])));
      }
    }
  }

  /**
   * Prepares a #type 'color' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderColor($element) {
    $element['#attributes']['type'] = 'color';
    Element::setAttributes($element, array('id', 'name', 'value'));
    static::setAttributes($element, array('form-color'));

    return $element;
  }

}
