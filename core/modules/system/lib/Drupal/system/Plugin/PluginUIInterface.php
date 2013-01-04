<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\PluginUIInterface.
 */

namespace Drupal\system\Plugin;

/**
 * Defines an interface for Plugin UI plugins.
 *
 * @todo This needs a lot more explanation.
 */
interface PluginUIInterface {

  /**
   * Creates a form array.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   Returns the form structure as an array.
   *
   * @todo Creates a form array for what?
   */
  public function form($form, &$form_state);

  /**
   * Validates form values from the form() method.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function formValidate($form, &$form_state);

  /**
   * Submits form values from the form() method.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function formSubmit($form, &$form_state);

}
