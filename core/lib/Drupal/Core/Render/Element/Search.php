<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides an HTML5 input element with type of "search".
 *
 * Usage example:
 * @code
 * $form['search'] = array(
 *   '#type' => 'search',
 *   '#title' => $this->t('Search'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("search")
 */
class Search extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
      '#autocomplete_route_name' => FALSE,
      '#process' => [
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderSearch'],
      ],
      '#theme' => 'input__search',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Prepares a #type 'search' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderSearch($element) {
    $element['#attributes']['type'] = 'search';
    Element::setAttributes($element, ['id', 'name', 'value', 'size', 'maxlength', 'placeholder']);
    static::setAttributes($element, ['form-search']);

    return $element;
  }

}
