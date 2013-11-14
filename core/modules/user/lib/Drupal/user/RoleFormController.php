<?php

/**
 * @file
 * Contains \Drupal\user\RoleFormController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the role entity edit forms.
 */
class RoleFormController extends EntityFormController {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $entity = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Role name'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => $this->t('The name for this role. Example: "Moderator", "Editorial board", "Site architect".'),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => array(
        'exists' => 'user_role_load',
      ),
    );
    $form['weight'] = array(
      '#type' => 'value',
      '#value' => $entity->get('weight'),
    );

    return parent::form($form, $form_state, $entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    // Disable delete of new and built-in roles.
    $actions['delete']['#access'] = !$this->entity->isNew() && !in_array($this->entity->id(), array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID));
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;

    // Prevent leading and trailing spaces in role names.
    $entity->set('label', trim($entity->label()));
    $uri = $entity->uri();
    if ($entity->save() == SAVED_UPDATED) {
      drupal_set_message($this->t('Role %label has been updated.', array('%label' => $entity->label())));
      watchdog('user', 'Role %label has been updated.', array('%label' => $entity->label()), WATCHDOG_NOTICE, l($this->t('Edit'), $uri['path']));
    }
    else {
      drupal_set_message($this->t('Role %label has been added.', array('%label' => $entity->label())));
      watchdog('user', 'Role %label has been added.', array('%label' => $entity->label()), WATCHDOG_NOTICE, l($this->t('Edit'), $uri['path']));
    }
    $form_state['redirect_route']['route_name'] = 'user.role_list';
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect_route'] = array(
      'route_name' => 'user.role_delete',
      'route_parameters' => array('user_role' => $this->entity->id()),
    );
  }

}
