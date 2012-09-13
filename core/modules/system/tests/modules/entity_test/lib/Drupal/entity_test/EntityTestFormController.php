<?php
/**
 * @file
 * Definition of Drupal\entity_test\EntityTestFormController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the test entity edit forms.
 */
class EntityTestFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $entity) {
    $form = parent::form($form, $form_state, $entity);

    $langcode = $this->getFormLangcode($form_state);
    $name = $entity->get('name', $langcode);
    $uid = $entity->get('uid', $langcode);

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => !empty($name) ? $name : '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#weight' => -10,
    );

    $form['uid'] = array(
      '#type' => 'textfield',
      '#title' => 'UID',
      '#default_value' => !empty($uid) ? $uid : '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#weight' => -10,
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    $entity = parent::submit($form, $form_state);
    $langcode = $this->getFormLangcode($form_state);

    // Updates multilingual properties.
    foreach (array('name', 'uid') as $property) {
      $entity->set($property, $form_state['values'][$property], $langcode);
    }

    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->getEntity($form_state);
    $is_new = $entity->isNew();
    $entity->save();

    $message = $is_new ? t('entity_test @id has been created.', array('@id' => $entity->id())) : t('entity_test @id has been updated.', array('@id' => $entity->id()));
    drupal_set_message($message);

    if ($entity->id()) {
      $form_state['redirect'] = 'entity-test/manage/' . $entity->id() . '/edit';
    }
    else {
      // Error on save.
      drupal_set_message(t('The entity could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $entity = $this->getEntity($form_state);
    $entity->delete();
    drupal_set_message(t('entity_test @id has been deleted.', array('@id' => $entity->id())));
    $form_state['redirect'] = '<front>';
  }
}
