<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides an action button form element.
 *
 * When the button is pressed:
 * - If #submit_button is TRUE (default), the form will be submitted to Drupal,
 *   where it is validated and rebuilt. The submit handler is not invoked.
 * - If #submit_button is FALSE, the button will act as a regular HTML button
 *   (with the 'type' attribute set to 'button') and will not trigger a form
 *   submission. This allows developers to define custom client-side behavior
 *   using JavaScript or other mechanisms.
 *
 * Properties:
 * - #limit_validation_errors: An array of form element keys that will block
 *   form submission when validation for these elements or any child elements
 *   fails. Specify an empty array to suppress all form validation errors.
 * - #value: The text to be shown on the button.
 * - #submit_button: This has a default value of TRUE. If set to FALSE, the
 *   'type' attribute is set to 'button.'
 *
 *
 * Usage Example:
 * @code
 * $form['actions']['preview'] = [
 *   '#type' => 'button',
 *   '#value' => $this->t('Preview'),
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Submit
 */
#[FormElement('button')]
class Button extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#name' => 'op',
      '#is_button' => TRUE,
      '#submit_button' => TRUE,
      '#executes_submit_callback' => FALSE,
      '#limit_validation_errors' => FALSE,
      '#process' => [
        [static::class, 'processButton'],
        [static::class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [static::class, 'preRenderButton'],
      ],
      '#theme_wrappers' => ['input__submit'],
    ];
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
   * Prepares a #type 'button' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #attributes, #button_type, #name, #submit_button,
   *   #value. The #button_type property accepts any value, though core themes
   *   have CSS that styles the following button_types appropriately:
   *   'primary', 'danger'.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderButton($element) {
    if ($element['#submit_button']) {
      $element['#attributes']['type'] = 'submit';
    }
    else {
      $element['#attributes']['type'] = 'button';
    }
    Element::setAttributes($element, ['id', 'name', 'value']);

    $element['#attributes']['class'][] = 'button';
    if (!empty($element['#button_type'])) {
      $element['#attributes']['class'][] = 'button--' . $element['#button_type'];
    }
    $element['#attributes']['class'][] = 'js-form-submit';
    $element['#attributes']['class'][] = 'form-submit';

    if (!empty($element['#attributes']['disabled'])) {
      $element['#attributes']['class'][] = 'is-disabled';
    }

    return $element;
  }

}
