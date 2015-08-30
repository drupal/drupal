<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormValidatorInterface.
 */

namespace Drupal\Core\Form;

/**
 * Provides an interface for validating form submissions.
 */
interface FormValidatorInterface {

  /**
   * Executes custom validation handlers for a given form.
   *
   * Button-specific handlers are checked first. If none exist, the function
   * falls back to form-level handlers.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form. If the user submitted the form by clicking
   *   a button with custom handler functions defined, those handlers will be
   *   stored here.
   */
  public function executeValidateHandlers(&$form, FormStateInterface &$form_state);

  /**
   * Validates user-submitted form data in the $form_state.
   *
   * @param $form_id
   *   A unique string identifying the form for validation, submission,
   *   theming, and hook_form_alter functions.
   * @param $form
   *   An associative array containing the structure of the form, which is
   *   passed by reference. Form validation handlers are able to alter the form
   *   structure (like #process and #after_build callbacks during form building)
   *   in case of a validation error. If a validation handler alters the form
   *   structure, it is responsible for validating the values of changed form
   *   elements in $form_state->getValues() to prevent form submit handlers from
   *   receiving unvalidated values.
   * @param $form_state
   *   The current state of the form. The current user-submitted data is stored
   *   in $form_state->getValues(), though form validation functions are passed
   *   an explicit copy of the values for the sake of simplicity. Validation
   *   handlers can also use $form_state to pass information on to submit
   *   handlers. For example:
   *     $form_state->set('data_for_submission', $data);
   *   This technique is useful when validation requires file parsing,
   *   web service requests, or other expensive requests that should
   *   not be repeated in the submission step.
   */
  public function validateForm($form_id, &$form, FormStateInterface &$form_state);

  /**
   * Sets a form_token error on the given form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return $this
   */
  public function setInvalidTokenError(FormStateInterface $form_state);

}
