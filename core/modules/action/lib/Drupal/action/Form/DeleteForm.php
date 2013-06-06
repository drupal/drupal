<?php

/**
 * @file
 * Contains \Drupal\action\Form\DeleteForm.
 */

namespace Drupal\action\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\system\ActionConfigEntityInterface;

/**
 * Builds a form to delete an action.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The action to be deleted.
   *
   * @var \Drupal\system\ActionConfigEntityInterface
   */
  protected $action;

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the action %action?', array('%action' => $this->action->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }


  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/config/system/actions';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'action_admin_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ActionConfigEntityInterface $action = NULL) {
    $this->action = $action;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->action->delete();

    watchdog('user', 'Deleted action %aid (%action)', array('%aid' => $this->action->id(), '%action' => $this->action->label()));
    drupal_set_message(t('Action %action was deleted', array('%action' => $this->action->label())));

    $form_state['redirect'] = 'admin/config/system/actions';
  }

}
