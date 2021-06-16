<?php

namespace Drupal\user;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the profile forms.
 *
 * @internal
 */
class ProfileForm extends AccountForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // The user account being edited.
    $account = $this->entity;

    $element['delete']['#type'] = 'submit';
    $element['delete']['#value'] = $this->t('Cancel account');
    $element['delete']['#submit'] = ['::editCancelSubmit'];
    $element['delete']['#access'] = $account->id() > 1 && $account->access('delete');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $account = $this->entity;
    $account->save();
    $form_state->setValue('uid', $account->id());

    $this->messenger()->addStatus($this->t('The changes have been saved.'));
  }

  /**
   * Provides a submit handler for the 'Cancel account' button.
   */
  public function editCancelSubmit($form, FormStateInterface $form_state) {
    $destination = [];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $destination = ['destination' => $query->get('destination')];
      $query->remove('destination');
    }
    // We redirect from user/%/edit to user/%/cancel to make the tabs disappear.
    $form_state->setRedirect(
      'entity.user.cancel_form',
      ['user' => $this->entity->id()],
      ['query' => $destination]
    );
  }

}
