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
  public function init(array &$form_state) {
    parent::init($form_state);

    drupal_set_title(t('Clone of @label', array('@label' => $this->entity->label())));
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::prepareForm().
   */
  protected function prepareEntity() {
    // Do not prepare the entity while it is being added.
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    parent::form($form, $form_state);

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#default_value' => '',
      '#maxlength' => 255,
      '#default_value' => t('Clone of @label', array('@label' => $this->entity->label())),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => 'views_get_view',
        'source' => array('label'),
      ),
      '#default_value' => '',
      '#description' => t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    );

    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $actions['submit'] = array(
      '#value' => t('Clone'),
      '#submit' => array(
        array($this, 'submit'),
      ),
    );
    return $actions;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::form().
   */
  public function submit(array $form, array &$form_state) {
    $original = parent::submit($form, $form_state);
    $this->entity = $original->createDuplicate();
    $this->entity->set('id', $form_state['values']['id']);
    $this->entity->save();

    // Redirect the user to the view admin form.
    $uri = $this->entity->uri();
    $form_state['redirect'] = $uri['path'];
    return $this->entity;
  }

}
