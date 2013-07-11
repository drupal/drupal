<?php

/**
 * @file
 * Definition of Drupal\user\ProfileFormController.
 */

namespace Drupal\user;

/**
 * Form controller for the profile forms.
 */
class ProfileFormController extends AccountFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    $account = $this->entity;

    $element['delete']['#type'] = 'submit';
    $element['delete']['#value'] = t('Cancel account');
    $element['delete']['#submit'] = array('user_edit_cancel_submit');
    $element['delete']['#access'] = $account->id() > 1 && (($account->id() == $GLOBALS['user']->id() && user_access('cancel account')) || user_access('administer users'));

    return $element;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    // @todo Consider moving this into the parent method.
    // Remove unneeded values.
    form_state_values_clean($form_state);
    parent::submit($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $account = $this->entity;
    $account->save();
    $form_state['values']['uid'] = $account->id();

    // Clear the page cache because pages can contain usernames and/or profile
    // information:
    cache_invalidate_tags(array('content' => TRUE));

    drupal_set_message(t('The changes have been saved.'));
  }
}
