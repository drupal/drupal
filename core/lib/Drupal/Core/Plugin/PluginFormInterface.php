<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\PluginFormInterface.
 */

namespace Drupal\Core\Plugin;

/**
 * Provides an interface for a plugin that contains a form.
 */
interface PluginFormInterface {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, array &$form_state);

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function validateConfigurationForm(array &$form, array &$form_state);

  /**
   * Form submission handler.
   *
   * To properly store submitted form values store them in $this->configuration.
   * @code
   *   $this->configuration['some_value'] = $form_state['values']['some_value'];
   * @endcode
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitConfigurationForm(array &$form, array &$form_state);

}
