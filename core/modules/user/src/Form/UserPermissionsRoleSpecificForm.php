<?php

namespace Drupal\user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Provides the user permissions administration form for a specific role.
 *
 * @internal
 */
class UserPermissionsRoleSpecificForm extends UserPermissionsForm {

  /**
   * The specific role for this form.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $userRole;

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return [$this->userRole->id() => $this->userRole];
  }

  /**
   * Builds the user permissions administration form for a specific role.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\user\RoleInterface|null $user_role
   *   (optional) The user role used for this form. Defaults to NULL.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RoleInterface $user_role = NULL) {
    $this->userRole = $user_role;
    return parent::buildForm($form, $form_state);
  }

}
