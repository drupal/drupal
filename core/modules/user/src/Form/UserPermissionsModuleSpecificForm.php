<?php

namespace Drupal\user\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the user permissions administration form for a specific role.
 *
 * @internal
 */
class UserPermissionsModuleSpecificForm extends UserPermissionsForm {

  /**
   * Builds the user permissions administration form for a specific module(s).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $modules
   *   (optional) One or more module names, comma-separated.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $modules = '') {
    $this->moduleList = explode(',', $modules);
    return parent::buildForm($form, $form_state);
  }

}
