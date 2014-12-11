<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Search.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a form input element for searching.
 *
 * This is commonly used to provide a filter or search box at the top of a
 * long listing page, to allow users to find specific items in the list for
 * faster input.
 *
 * @FormElement("search")
 */
class Search extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
      '#autocomplete_route_name' => FALSE,
      '#process' => array(
        array($class, 'processAutocomplete'),
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderSearch'),
      ),
      '#theme' => 'input__search',
      '#theme_wrappers' => array('form_element'),
    );
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
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-search'));

    return $element;
  }

}
