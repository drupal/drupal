<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a slider for input of a number within a specific range.
 *
 * Provides an HTML5 input element with type of "range".
 *
 * Properties:
 * - #min: Minimum value (defaults to 0).
 * - #max: Maximum value (defaults to 100).
 * Refer to \Drupal\Core\Render\Element\Number for additional properties.
 *
 * Usage example:
 * @code
 * $form['quantity'] = array(
 *   '#type' => 'range',
 *   '#title' => $this->t('Quantity'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Number
 *
 * @FormElement("range")
 */
class Range extends Number {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    return array(
      '#min' => 0,
      '#max' => 100,
      '#pre_render' => array(
        array($class, 'preRenderRange'),
      ),
      '#theme' => 'input__range',
    ) + $info;
  }

  /**
   * Prepares a #type 'range' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #attributes,
   *   #step.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderRange($element) {
    $element['#attributes']['type'] = 'range';
    Element::setAttributes($element, array('id', 'name', 'value', 'step', 'min', 'max'));
    static::setAttributes($element, array('form-range'));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === '') {
      $offset = ($element['#max'] - $element['#min']) / 2;

      // Round to the step.
      if (strtolower($element['#step']) != 'any') {
        $steps = round($offset / $element['#step']);
        $offset = $element['#step'] * $steps;
      }

      return $element['#min'] + $offset;
    }
  }

}
