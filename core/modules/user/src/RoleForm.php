<?php

namespace Drupal\user;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the role entity edit forms.
 *
 * @internal
 */
class RoleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role name'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => $this->t('The name for this role. Example: "Moderator", "Editorial board", "Site architect".'),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => [
        'exists' => ['\Drupal\user\Entity\Role', 'load'],
      ],
    ];
    $form['weight'] = [
      '#type' => 'value',
      '#value' => $entity->getWeight(),
    ];

    return parent::form($form, $form_state, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Prevent leading and trailing spaces in role names.
    $entity->set('label', trim($entity->label()));
    $status = $entity->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Role %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('user')->notice('Role %label has been updated.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    else {
      drupal_set_message($this->t('Role %label has been added.', ['%label' => $entity->label()]));
      $this->logger('user')->notice('Role %label has been added.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    $form_state->setRedirect('entity.user_role.collection');
  }

}
