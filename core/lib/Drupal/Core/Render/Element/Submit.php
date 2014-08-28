<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Submit.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a form submit button.
 *
 * Submit buttons are processed the same as regular buttons, except they trigger
 * the form's submit handler.
 *
 * @FormElement("submit")
 */
class Submit extends Button {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#executes_submit_callback' => TRUE,
    ) + parent::getInfo();
  }

}
