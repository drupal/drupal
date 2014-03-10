<?php

/**
 * @file
 * Contains \Drupal\views_ui\ViewCloneFormController.
 */

namespace Drupal\views_ui;

/**
 * Form controller for the Views clone form.
 */
class ViewCloneFormController extends ViewFormControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    // Do not prepare the entity while it is being added.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    parent::form($form, $form_state);

    $form['#title'] = $this->t('Clone of @label', array('@label' => $this->entity->label()));

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#maxlength' => 255,
      '#default_value' => $this->t('Clone of @label', array('@label' => $this->entity->label())),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => '\Drupal\views\Views::getView',
        'source' => array('label'),
      ),
      '#default_value' => '',
      '#description' => $this->t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions['submit'] = array(
      '#value' => $this->t('Clone'),
      '#submit' => array(
        array($this, 'submit'),
      ),
    );
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $original = parent::submit($form, $form_state);
    $this->entity = $original->createDuplicate();
    $this->entity->set('id', $form_state['values']['id']);
    $this->entity->save();

    // Redirect the user to the view admin form.
    $form_state['redirect_route'] = $this->entity->urlInfo('edit-form');
    return $this->entity;
  }

}
