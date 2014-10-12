<?php

/**
 * @file
 * Contains Drupal\system\FileAjaxForm.
 */

namespace Drupal\system;

use Drupal\Core\Form\FormStateInterface;

/**
 * Wrapper for Ajax forms data and commands, avoiding a multi-return-value tuple.
 *
 * @ingroup ajax
 */
class FileAjaxForm {

  /**
   * The form to cache.
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
   * The unique form ID.
   *
   * @var string
   */
  protected $formId;

  /**
   * The unique form build ID.
   *
   * @var string
   */
  protected $formBuildId;

  /**
   * The array of ajax commands.
   *
   * @var \Drupal\Core\Ajax\CommandInterface[]
   */
  protected $commands;

  /**
   * Constructs a FileAjaxForm object.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Ajax\string|string $form_id
   *   The unique form ID.
   * @param \Drupal\Core\Ajax\string|string $form_build_id
   *   The unique form build ID.
   * @param \Drupal\Core\Ajax\CommandInterface[] $commands
   *   The ajax commands.
   */
  public function __construct(array $form, FormStateInterface $form_state, $form_id, $form_build_id, array $commands) {
    $this->form = $form;
    $this->formState = $form_state;
    $this->formId = $form_id;
    $this->formBuildId = $form_build_id;
    $this->commands = $commands;
  }

  /**
   * Gets all AJAX commands.
   *
   * @return \Drupal\Core\Ajax\CommandInterface[]
   *   Returns all previously added AJAX commands.
   */
  public function getCommands() {
    return $this->commands;
  }

  /**
   * Gets the form definition.
   *
   * @return array
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Gets the unique form build ID.
   *
   * @return string
   */
  public function getFormBuildId() {
    return $this->formBuildId;
  }

  /**
   * Gets the unique form ID.
   *
   * @return string
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   * Gets the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getFormState() {
    return $this->formState;
  }

}
