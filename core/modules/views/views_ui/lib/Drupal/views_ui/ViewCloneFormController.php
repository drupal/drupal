<?php

/**
 * @file
 * Contains \Drupal\views_ui\ViewCloneFormController.
 */

namespace Drupal\views_ui;

use Drupal\Core\Entity\EntityInterface;

/**
 * Form controller for the Views clone form.
 */
class ViewCloneFormController extends ViewFormControllerBase {

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::prepareForm().
   */
  protected function prepareEntity(EntityInterface $entity) {
    // Do not prepare the entity while it is being added.
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $entity) {
    parent::form($form, $form_state, $entity);

    $form['human_name'] = array(
      '#type' => 'textfield',
      '#title' => t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#default_value' => '',
      '#maxlength' => 255,
      '#default_value' => t('Clone of @human_name', array('@human_name' => $entity->getHumanName())),
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => 'views_get_view',
        'source' => array('human_name'),
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
    $entity = parent::submit($form, $form_state);
    $entity->setOriginalID(NULL);
    $entity->save();

    // Redirect the user to the view admin form.
    $uri = $entity->uri();
    $form_state['redirect'] = $uri['path'] . '/edit';
    return $entity;
  }

}
