<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ConfigurableActionInterface.
 */

namespace Drupal\Core\Action;

use Drupal\Core\Action\ActionInterface;

/**
 * Provides an interface for an Action plugin.
 *
 * @see \Drupal\Core\Annotation\Operation
 * @see \Drupal\Core\Action\OperationManager
 */
interface ConfigurableActionInterface extends ActionInterface {

  /**
   * Returns this plugin's configuration.
   *
   * @return array
   *   An array of this action plugin's configuration.
   */
  public function getConfiguration();

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
  public function form(array $form, array &$form_state);

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function validate(array &$form, array &$form_state);

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function submit(array &$form, array &$form_state);

}
