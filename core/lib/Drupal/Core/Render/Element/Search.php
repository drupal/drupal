<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides an HTML5 input element with type of "search".
 *
 * Usage example:
 * @code
 * $form['search'] = [
 *   '#type' => 'search',
 *   '#title' => $this->t('Search'),
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 */
#[FormElement('search')]
class Search extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
      '#autocomplete_route_name' => FALSE,
      '#process' => [
        [static::class, 'processAutocomplete'],
        [static::class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [static::class, 'preRenderSearch'],
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
