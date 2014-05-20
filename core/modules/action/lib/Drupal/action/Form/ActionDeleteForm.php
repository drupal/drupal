<?php

/**
 * @file
 * Contains \Drupal\action\Form\ActionDeleteForm.
 */

namespace Drupal\action\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Builds a form to delete an action.
 */
class ActionDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the action %action?', array('%action' => $this->entity->label()));
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
  public function getCancelRoute() {
    return new Url('action.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();

    watchdog('user', 'Deleted action %aid (%action)', array('%aid' => $this->entity->id(), '%action' => $this->entity->label()));
    drupal_set_message($this->t('Action %action was deleted', array('%action' => $this->entity->label())));

    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
