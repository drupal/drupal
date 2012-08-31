<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\wizard\WizardInterface.
 */

namespace Drupal\views\Plugin\views\wizard;

/**
 * Defines a common interface for Views Wizard plugins.
 */
interface WizardInterface {

  /**
   * Constructs a wizard plugin object.
   *
   * @param array $definition
   *   The information stored in the annotation definition.
   */
  function __construct(array $definition);

  /**
   * For AJAX callbacks to build other elements in the "show" form.
   */
  function build_form($form, &$form_state);

  /**
   * Validate form and values.
   *
   * @param array $form
   *   The full wizard form array.
   * @param array $form_state
   *   The current state of the wizard form.
   *
   * @return array
   *   An empty array if the view is valid; an array of error strings if it is
   *   not.
   */
  public function validate(array $form, array &$form_state);

  /**
   * Creates a view from values that have already been validated.
   *
   * @param array $form
   *   The full wizard form array.
   * @param array $form_state
   *   The current state of the wizard form.
   *
   * @return Drupal\views\View
   *   The created view object.
   *
   * @throws Drupal\views\Plugin\views\wizard\WizardException
   */
  function create_view(array $form, array &$form_state);

}
