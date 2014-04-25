<?php

/**
 * @file
 * Contains \Drupal\user\ProfileForm.
 */

namespace Drupal\user;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Language\LanguageManager;

/**
 * Form controller for the profile forms.
 */
class ProfileForm extends AccountForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManager $language_manager, QueryFactory $entity_query) {
    parent::__construct($entity_manager, $language_manager, $entity_query);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);

    // The user account being edited.
    $account = $this->entity;

    // The user doing the editing.
    $user = $this->currentUser();
    $element['delete']['#type'] = 'submit';
    $element['delete']['#value'] = $this->t('Cancel account');
    $element['delete']['#submit'] = array(array($this, 'editCancelSubmit'));
    $element['delete']['#access'] = $account->id() > 1 && (($account->id() == $user->id() && $user->hasPermission('cancel account')) || $user->hasPermission('administer users'));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $account = $this->entity;
    $account->save();
    $form_state['values']['uid'] = $account->id();

    // Clear the page cache because pages can contain usernames and/or profile
    // information:
    Cache::invalidateTags(array('content' => TRUE));

    drupal_set_message($this->t('The changes have been saved.'));
  }

  /**
   * Provides a submit handler for the 'Cancel account' button.
   */
  public function editCancelSubmit($form, &$form_state) {
    $destination = array();
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $destination = array('destination' => $query->get('destination'));
      $query->remove('destination');
    }
    // We redirect from user/%/edit to user/%/cancel to make the tabs disappear.
    $form_state['redirect_route'] = array(
      'route_name' => 'user.cancel',
      'route_parameters' => array('user' => $this->entity->id()),
      'options' => array('query' => $destination),
    );
  }

}
