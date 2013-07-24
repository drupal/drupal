<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserPermissionsRoleSpecificForm.
 */

namespace Drupal\user\Form;

/**
 * Provides the user permissions administration form for a specific role.
 */
class UserPermissionsRoleSpecificForm extends UserPermissionsForm {

  /**
   * The specific role for this form.
   *
   * @var string
   */
  protected $roleId;

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return array($this->roleId => $this->roleStorage->load($this->roleId));
  }

  /**
   * {@inheritdoc}
   *
   * @param string $role_id
   *   The user role ID used for this form.
   */
  public function buildForm(array $form, array &$form_state, $role_id = NULL) {
    $this->roleId = $role_id;
    return parent::buildForm($form, $form_state);
  }

}
