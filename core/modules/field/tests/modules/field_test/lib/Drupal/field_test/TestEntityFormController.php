<?php

/**
 * @file
 * Definition of Drupal\field_test\TestEntityFormController.
 */

namespace Drupal\field_test;

use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the test entity edit forms.
 */
class TestEntityFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    if (!$entity->isNew()) {
      $form['revision'] = array(
        '#access' => user_access('administer field_test content'),
        '#type' => 'checkbox',
        '#title' => t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 100,
      );
    }
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $is_new = $entity->isNew();
    $entity->save();

    $message = $is_new ? t('test_entity @id has been created.', array('@id' => $entity->id())) : t('test_entity @id has been updated.', array('@id' => $entity->id()));
    drupal_set_message($message);

    if ($entity->id()) {
      $form_state['redirect'] = 'test-entity/manage/' . $entity->id() . '/edit';
    }
    else {
      // Error on save.
      drupal_set_message(t('The entity could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }
  }
}
