<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\FormElement;

/**
 * Provides a form element for storage of internal information.
 *
 * Unlike \Drupal\Core\Render\Element\Hidden, this information is not sent to
 * the browser in a hidden form field, but only stored in the form array for use
 * in validation and submit processing.
 *
 * Properties:
 * - #value: The value of the form element that cannot be edited by the user.
 *
 * Usage Example:
 * @code
 * $form['entity_id'] = ['#type' => 'value', '#value' => $entity_id];
 * @endcode
 */
#[FormElement('value')]
class Value extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
    ];
  }

}
