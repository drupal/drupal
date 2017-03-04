<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a form element for an HTML 'hidden' input element.
 *
 * Specify either #default_value or #value but not both.
 *
 * Properties:
 * - #default_value: The initial value of the form element. JavaScript may
 *   alter the value prior to submission.
 * - #value: The value of the form element. The Form API ensures that this
 *   value remains unchanged by the browser.
 *
 * Usage example:
 * @code
 * $form['entity_id'] = array('#type' => 'hidden', '#value' => $entity_id);
 * @endcode
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
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderHidden'],
      ],
      '#theme' => 'input__hidden',
    ];
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
    Element::setAttributes($element, ['name', 'value']);

    return $element;
  }

}
