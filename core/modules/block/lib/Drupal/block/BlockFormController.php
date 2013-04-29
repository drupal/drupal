<?php

/**
 * @file
 * Contains \Drupal\block\BlockFormController.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityFormController;

/**
 * Provides form controller for block instance forms.
 */
class BlockFormController extends EntityFormController {

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    return $this->entity->getPlugin()->form($form, $form_state);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save block');
    return $actions;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    $entity = $this->entity;
    $entity->getPlugin()->validate($form, $form_state);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);

    $entity = $this->entity;
    // Call the plugin submit handler.
    $entity->getPlugin()->submit($form, $form_state);

    // Save the settings of the plugin.
    $entity->set('settings', $entity->getPlugin()->getConfig());
    $entity->save();
  }

}
