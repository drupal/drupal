<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Value.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a form element for storage of internal information.
 *
 * Unlike \Drupal\Core\Render\Element\Hidden, this information is not
 * sent to the browser in a hidden form field, but is just stored in the form
 * array for use in validation and submit processing.
 *
 * @FormElement("value")
 */
class Value extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#input' => TRUE,
    );
  }

}
