<?php

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for input of a URL.
 *
 * Properties:
 * - #default_value: A valid URL string.
 *
 * Usage example:
 * @code
 * $form['homepage'] = array(
 *   '#type' => 'url',
 *   '#title' => t('Home Page'),
 *   '#size' => 30,
 *   ...
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("url")
 */
class Url extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#size' => 60,
      '#maxlength' => 255,
      '#autocomplete_route_name' => FALSE,
      '#process' => array(
        array($class, 'processAutocomplete'),
        array($class, 'processAjaxForm'),
        array($class, 'processPattern'),
      ),
      '#element_validate' => array(
        array($class, 'validateUrl'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderUrl'),
      ),
      '#theme' => 'input__url',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * Form element validation handler for #type 'url'.
   *
   * Note that #maxlength and #required is validated by _form_validate() already.
   */
  public static function validateUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    if ($value !== '' && !UrlHelper::isValid($value, TRUE)) {
      $form_state->setError($element, t('The URL %url is not valid.', array('%url' => $value)));
    }
  }

  /**
   * Prepares a #type 'url' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderUrl($element) {
    $element['#attributes']['type'] = 'url';
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-url'));

    return $element;
  }

}
