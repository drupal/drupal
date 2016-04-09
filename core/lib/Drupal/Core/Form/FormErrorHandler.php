<?php

namespace Drupal\Core\Form;

use Drupal\Core\Render\Element;

/**
 * Handles form errors.
 */
class FormErrorHandler implements FormErrorHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function handleFormErrors(array &$form, FormStateInterface $form_state) {
    // After validation check if there are errors.
    if ($errors = $form_state->getErrors()) {
      // Display error messages for each element.
      $this->displayErrorMessages($form, $form_state);

      // Loop through and assign each element its errors.
      $this->setElementErrorsFromFormState($form, $form_state);
    }

    return $this;
  }

  /**
   * Loops through and displays all form errors.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function displayErrorMessages(array $form, FormStateInterface $form_state) {
    $errors = $form_state->getErrors();

    // Loop through all form errors and set an error message.
    foreach ($errors as $error) {
      $this->drupalSetMessage($error, 'error');
    }
  }

  /**
   * Stores the errors of each element directly on the element.
   *
   * We must provide a way for non-form functions to check the errors for a
   * specific element. The most common usage of this is a #pre_render callback.
   *
   * @param array $elements
   *   An associative array containing the structure of a form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setElementErrorsFromFormState(array &$elements, FormStateInterface &$form_state) {
    // Recurse through all children.
    foreach (Element::children($elements) as $key) {
      if (isset($elements[$key]) && $elements[$key]) {
        $this->setElementErrorsFromFormState($elements[$key], $form_state);
      }
    }

    // Store the errors for this element on the element directly.
    $elements['#errors'] = $form_state->getError($elements);
  }

  /**
   * Wraps drupal_set_message().
   *
   * @codeCoverageIgnore
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }

}
