<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\PluginFormInterface.
 */

namespace Drupal\Core\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for a plugin that contains a form.
 *
 * Plugin forms are usually contained in other forms. In order to know where the
 * plugin form is located in the parent form, #parents and #array_parents must
 * be known, but these are not available during the initial build phase. In
 * order to have these properties available when building the plugin form's
 * elements, let buildConfigurationForm() return a form element that has a
 * #process callback and build the rest of the form in the callback. By the time
 * the callback is executed, the element's #parents and #array_parents
 * properties will have been set by the form API. For more documentation on
 * #parents and #array_parents, see
 * https://api.drupal.org/api/drupal/developer!topics!forms_api_reference.html/8.
 *
 * @ingroup plugin_api
 */
interface PluginFormInterface {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state);

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state);

}
