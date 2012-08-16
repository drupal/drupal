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

  function __construct($plugin);

  /**
   * For AJAX callbacks to build other elements in the "show" form.
   */
  function build_form($form, &$form_state);

  /**
   * Validate form and values.
   *
   * @return an array of form errors.
   */
  function validate($form, &$form_state);

  /**
   * Create a new View from form values.
   *
   * @return a view object.
   *
   * @throws ViewsWizardException in the event of a problem.
   */
  function create_view($form, &$form_state);

}
