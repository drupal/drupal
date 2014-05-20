<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutSetForm.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\EntityForm;

/**
 * Form controller for the shortcut set entity edit forms.
 */
class ShortcutSetForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Set name'),
      '#description' => t('The new set is created by copying items from your default shortcut set.'),
      '#required' => TRUE,
      '#default_value' => $entity->label(),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => '\Drupal\shortcut\Entity\ShortcutSet::load',
        'source' => array('label'),
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ),
      '#default_value' => $entity->id(),
      '#disabled' => !$entity->isNew(),
      // This id could be used for menu name.
      '#maxlength' => 23,
    );

    $form['actions']['submit']['#value'] = t('Create new set');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
    $entity = $this->entity;
    // Check to prevent a duplicate title.
    if ($form_state['values']['label'] != $entity->label() && shortcut_set_title_exists($form_state['values']['label'])) {
      $this->setFormError('label', $form_state, $this->t('The shortcut set %name already exists. Choose another name.', array('%name' => $form_state['values']['label'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();
    $entity->save();

    if ($is_new) {
      drupal_set_message(t('The %set_name shortcut set has been created. You can edit it from this page.', array('%set_name' => $entity->label())));
    }
    else {
      drupal_set_message(t('Updated set name to %set-name.', array('%set-name' => $entity->label())));
    }
    $form_state['redirect_route'] = $this->entity->urlInfo('customize-form');
  }

}
