<?php

namespace Drupal\user;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Form handler for the profile forms.
 */
class ProfileForm extends AccountForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, QueryFactory $entity_query) {
    parent::__construct($entity_manager, $language_manager, $entity_query);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // The user account being edited.
    $account = $this->entity;

    // The user doing the editing.
    $user = $this->currentUser();
    $element['delete']['#type'] = 'submit';
    $element['delete']['#value'] = $this->t('Cancel account');
    $element['delete']['#submit'] = array('::editCancelSubmit');
    $element['delete']['#access'] = $account->id() > 1 && (($account->id() == $user->id() && $user->hasPermission('cancel account')) || $user->hasPermission('administer users'));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $account = $this->entity;
    $account->save();
    $form_state->setValue('uid', $account->id());

    drupal_set_message($this->t('The changes have been saved.'));
  }

  /**
   * Provides a submit handler for the 'Cancel account' button.
   */
  public function editCancelSubmit($form, FormStateInterface $form_state) {
    $destination = array();
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $destination = array('destination' => $query->get('destination'));
      $query->remove('destination');
    }
    // We redirect from user/%/edit to user/%/cancel to make the tabs disappear.
    $form_state->setRedirect(
      'entity.user.cancel_form',
      array('user' => $this->entity->id()),
      array('query' => $destination)
    );
  }

}
