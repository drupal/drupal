<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for entering a password, with hidden text.
 *
 * Usage example:
 * @code
 * $form['pass'] = array(
 *   '#type' => 'password',
 *   '#title => t('Password'),
 *   '#size' => 25,
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\PasswordConfirm
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("password")
 */
class Password extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
      '#process' => array(
        array($class, 'processAjaxForm'),
        array($class, 'processPattern'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderPassword'),
      ),
      '#theme' => 'input__password',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * Prepares a #type 'password' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderPassword($element) {
    $element['#attributes']['type'] = 'password';
    Element::setAttributes($element, array('id', 'name', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-text'));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // This should be a string, but allow other scalars since they might be
      // valid input in programmatic form submissions.
      return is_scalar($input) ? (string) $input : '';
    }
    return NULL;
  }

}
