<?php

namespace Drupal\Core\Render\Element;

/**
 * Provides a form submit button.
 *
 * Submit buttons are processed the same as regular buttons, except they trigger
 * the form's submit handler.
 *
 * Properties:
 * - #submit: Specifies an alternate callback for form submission when the
 *   submit button is pressed.  Use '::methodName' format or an array containing
 *   the object and method name (for example, [ $this, 'methodName'] ).
 * - #value: The text to be shown on the button.
 *
 * Usage Example:
 * @code
 * $form['actions']['submit'] = array(
 *   '#type' => 'submit',
 *   '#value' => $this->t('Save'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Button
 *
 * @FormElement("submit")
 */
class Submit extends Button {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#executes_submit_callback' => TRUE,
    ] + parent::getInfo();
  }

}
