<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserRoleDelete.
 */

namespace Drupal\user\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for Role entity.
 */
class UserRoleDelete extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the role %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('user.role_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    watchdog('user', 'Role %name has been deleted.', array('%name' => $this->entity->label()));
    drupal_set_message($this->t('Role %name has been deleted.', array('%name' => $this->entity->label())));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
