<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormAjaxException.
 */

namespace Drupal\Core\Form;

/**
 * Custom exception to break out of AJAX form processing.
 */
class FormAjaxException extends \Exception {

  /**
   * The form definition.
   *
   * @var array
   */
  protected $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Constructs a FormAjaxException object.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $message
   *   (optional) The exception message.
   * @param int $code
   *   (optional) A user defined exception code.
   * @param \Exception $previous
   *   (optional) The previous exception for nested exceptions.
   */
  public function __construct(array $form, FormStateInterface $form_state, $message = "", $code = 0, \Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->form = $form;
    $this->formState = $form_state;
  }

  /**
   * Gets the form definition.
   *
   * @return array
   *   The form structure.
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Gets the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The current state of the form.
   */
  public function getFormState() {
    return $this->formState;
  }

}
