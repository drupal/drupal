<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserRoleDelete.
 */

namespace Drupal\user\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\user\RoleInterface;

/**
 * Provides a deletion confirmation form for Role entity.
 */
class UserRoleDelete extends ConfirmFormBase {

  /**
   * The role being deleted.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $role;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'user_admin_role_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the role %name?', array('%name' => $this->role->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/people/roles';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\user\RoleInterface $user_role
   *   The role being deleted.
   */
  public function buildForm(array $form, array &$form_state, RoleInterface $user_role = NULL) {
    $this->role = $user_role;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->role->delete();
    watchdog('user', 'Role %name has been deleted.', array('%name' => $this->role->label()));
    drupal_set_message(t('Role %name has been deleted.', array('%name' => $this->role->label())));
    $form_state['redirect'] = 'admin/people/roles';
  }

}
