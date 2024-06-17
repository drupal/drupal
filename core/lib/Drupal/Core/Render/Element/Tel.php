<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for entering a telephone number.
 *
 * Provides an HTML5 input element with type of "tel". It provides no special
 * validation.
 *
 * Properties:
 * - #size: The size of the input element in characters.
 * - #pattern: A string for the native HTML5 pattern attribute.
 *
 * Usage example:
 * @code
 * $form['phone'] = [
 *   '#type' => 'tel',
 *   '#title' => $this->t('Phone'),
 *   '#pattern' => '[^\d]*',
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element
 */
#[FormElement('tel')]
class Tel extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#size' => 30,
      '#maxlength' => 128,
      '#autocomplete_route_name' => FALSE,
      '#process' => [
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
        [$class, 'processPattern'],
      ],
      '#pre_render' => [
        [$class, 'preRenderTel'],
      ],
      '#theme' => 'input__tel',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Prepares a #type 'tel' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderTel($element) {
    $element['#attributes']['type'] = 'tel';
    Element::setAttributes($element, ['id', 'name', 'value', 'size', 'maxlength', 'placeholder']);
    static::setAttributes($element, ['form-tel']);

    return $element;
  }

}
