<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for a single radio button.
 *
 * This is an internal element that is primarily used to render the radios form
 * element. Refer to \Drupal\Core\Render\Element\Radios for more documentation.
 *
 * @see \Drupal\Core\Render\Element\Radios
 * @see \Drupal\Core\Render\Element\Checkbox
 */
#[FormElement('radio')]
class Radio extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderRadio'],
      ],
      '#theme' => 'input__radio',
      '#theme_wrappers' => ['form_element'],
      '#title_display' => 'after',
    ];
  }

  /**
   * Prepares a #type 'radio' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #required, #return_value, #value, #attributes, #title,
   *   #description. The #name property will be sanitized before output. This is
   *   currently done by initializing Drupal\Core\Template\Attribute with all
   *   the attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderRadio($element) {
    $element['#attributes']['type'] = 'radio';
    Element::setAttributes($element, ['id', 'name', '#return_value' => 'value']);

    // To avoid auto-casting during '==' we convert $element['#value'] and
    // $element['#return_value'] to strings. It will prevent wrong true-checking
    // for both cases: 0 == 'string' and 'string' == 0, this will occur because
    // all numeric array values will be integers and all submitted values will
    // be strings. Array values are never valid for radios and are skipped. To
    // account for FALSE and empty string values in the #return_value, we will
    // consider any #value that evaluates to empty to be the same as any
    // #return_value that evaluates to empty.
    if (isset($element['#return_value']) &&
      $element['#value'] !== FALSE &&
      !is_array($element['#value']) &&
      ((empty($element['#value']) && empty($element['#return_value'])) || (string) $element['#value'] === (string) $element['#return_value'])) {
      $element['#attributes']['checked'] = 'checked';
    }
    static::setAttributes($element, ['form-radio']);

    return $element;
  }

}
