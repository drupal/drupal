<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Button.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides an action button form element.
 *
 * When the button is pressed, the form will be submitted to Drupal, where it is
 * validated and rebuilt. The submit handler is not invoked.
 *
 * @FormElement("button")
 */
class Button extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#name' => 'op',
      '#is_button' => TRUE,
      '#executes_submit_callback' => FALSE,
      '#limit_validation_errors' => FALSE,
      '#process' => array(
        array($class, 'processButton'),
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderButton'),
      ),
      '#theme_wrappers' => array('input__submit'),
    );
  }

  /**
   * Processes a form button element.
   */
  public static function processButton(&$element, FormStateInterface $form_state, &$complete_form) {
    // If this is a button intentionally allowing incomplete form submission
    // (e.g., a "Previous" or "Add another item" button), then also skip
    // client-side validation.
    if (isset($element['#limit_validation_errors']) && $element['#limit_validation_errors'] !== FALSE) {
      $element['#attributes']['formnovalidate'] = 'formnovalidate';
    }
    return $element;
  }

  /**
   * Prepares a #type 'button' render element for theme_input().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #attributes, #button_type, #name, #value.
   *
   * The #button_type property accepts any value, though core themes have CSS that
   * styles the following button_types appropriately: 'primary', 'danger'.
   *
   * @return array
   *   The $element with prepared variables ready for theme_input().
   */
  public static function preRenderButton($element) {
    $element['#attributes']['type'] = 'submit';
    Element::setAttributes($element, array('id', 'name', 'value'));

    $element['#attributes']['class'][] = 'button';
    if (!empty($element['#button_type'])) {
      $element['#attributes']['class'][] = 'button--' . $element['#button_type'];
    }
    // @todo Various JavaScript depends on this button class.
    $element['#attributes']['class'][] = 'form-submit';

    if (!empty($element['#attributes']['disabled'])) {
      $element['#attributes']['class'][] = 'is-disabled';
    }

    return $element;
  }

}
