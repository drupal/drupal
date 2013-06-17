<?php

/**
 * @file
 * Contains \Drupal\action\Form\ActionDeleteForm.
 */

namespace Drupal\action\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds a form to delete an action.
 */
class ActionDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the action %action?', array('%action' => $this->entity->label()));
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
  public function getCancelPath() {
    return 'admin/config/system/actions';
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();

    watchdog('user', 'Deleted action %aid (%action)', array('%aid' => $this->entity->id(), '%action' => $this->entity->label()));
    drupal_set_message(t('Action %action was deleted', array('%action' => $this->entity->label())));

    $form_state['redirect'] = 'admin/config/system/actions';
  }

}
