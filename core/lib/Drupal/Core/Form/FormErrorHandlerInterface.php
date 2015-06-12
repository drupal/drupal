<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormErrorHandlerInterface.
 */

namespace Drupal\Core\Form;

/**
 * Provides an interface for handling form errors.
 */
interface FormErrorHandlerInterface {

  /**
   * Handles form errors after form validation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return $this
   */
  public function handleFormErrors(array &$form, FormStateInterface $form_state);

}
