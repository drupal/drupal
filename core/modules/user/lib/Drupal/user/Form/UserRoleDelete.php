<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserRoleDelete.
 */

namespace Drupal\user\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides a deletion confirmation form for Role entity.
 */
class UserRoleDelete extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the role %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/people/roles';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    watchdog('user', 'Role %name has been deleted.', array('%name' => $this->entity->label()));
    drupal_set_message(t('Role %name has been deleted.', array('%name' => $this->entity->label())));
    $form_state['redirect'] = 'admin/people/roles';
  }

}
