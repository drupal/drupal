<?php

/**
 * @file
 * Contains \Drupal\user\RoleForm.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;

/**
 * Form controller for the role entity edit forms.
 */
class RoleForm extends EntityForm {

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
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;

    // Prevent leading and trailing spaces in role names.
    $entity->set('label', trim($entity->label()));
    $status = $entity->save();

    $edit_link = \Drupal::linkGenerator()->generateFromUrl($this->t('Edit'), $this->entity->urlInfo());
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Role %label has been updated.', array('%label' => $entity->label())));
      watchdog('user', 'Role %label has been updated.', array('%label' => $entity->label()), WATCHDOG_NOTICE, $edit_link);
    }
    else {
      drupal_set_message($this->t('Role %label has been added.', array('%label' => $entity->label())));
      watchdog('user', 'Role %label has been added.', array('%label' => $entity->label()), WATCHDOG_NOTICE, $edit_link);
    }
    $form_state['redirect_route']['route_name'] = 'user.role_list';
  }

}
