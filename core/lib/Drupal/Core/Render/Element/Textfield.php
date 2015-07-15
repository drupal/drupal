<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Textfield.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a one-line text field form element.
 *
 * Properties:
 * - #maxlength: Maximum number of characters of input allowed.
 * - #size: The size of the input element in characters.
 * - #autocomplete_route_name: A route to be used as callback URL by the
 *   autocomplete JavaScript library.
 * - #autocomplete_route_parameters: An array of parameters to be used in
 *   conjunction with the route name.
 *
 * Usage example:
 * @code
 * $form['title'] = array(
 *   '#type' => 'textfield',
 *   '#title' => t('Subject'),
 *   '#default_value' => $node->title,
 *   '#size' => 60,
 *   '#maxlength' => 128,
 * '#required' => TRUE,
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Color
 * @see \Drupal\Core\Render\Element\Email
 * @see \Drupal\Core\Render\Element\MachineName
 * @see \Drupal\Core\Render\Element\Number
 * @see \Drupal\Core\Render\Element\Password
 * @see \Drupal\Core\Render\Element\PasswordConfirm
 * @see \Drupal\Core\Render\Element\Range
 * @see \Drupal\Core\Render\Element\Tel
 * @see \Drupal\Core\Render\Element\Url
 *
 * @FormElement("textfield")
 */
class Textfield extends FormElement {

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
        array($class, 'processPattern'),
        array($class, 'processGroup'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderTextfield'),
        array($class, 'preRenderGroup'),
      ),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // Equate $input to the form value to ensure it's marked for
      // validation.
      return str_replace(array("\r", "\n"), '', $input);
    }
  }

  /**
   * Prepares a #type 'textfield' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderTextfield($element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-text'));

    return $element;
  }

}
