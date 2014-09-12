<?php

/**
 * @file
 * Contains \Drupal\field_test\Form\NestedEntityTestForm.
 */

namespace Drupal\field_test\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Provides a form for field_test routes.
 */
class NestedEntityTestForm extends FormBase {

  /**
   * {@inheritdoc]
   */
  public function getFormId() {
    return 'field_test_entity_nested_form';
  }

  /**
   * {@inheritdoc]
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity_1 = NULL, EntityInterface $entity_2 = NULL) {
    // First entity.
    $form_state->set('entity_1', $entity_1);
    $form_display_1 = EntityFormDisplay::collectRenderDisplay($entity_1, 'default');
    $form_state->set('form_display_1', $form_display_1);
    $form_display_1->buildForm($entity_1, $form, $form_state);

    // Second entity.
    $form_state->set('entity_2', $entity_2);
    $form_display_2 = EntityFormDisplay::collectRenderDisplay($entity_2, 'default');
    $form_state->set('form_display_2', $form_display_2);
    $form['entity_2'] = array(
      '#type' => 'details',
      '#title' => t('Second entity'),
      '#tree' => TRUE,
      '#parents' => array('entity_2'),
      '#weight' => 50,
    );

    $form_display_2->buildForm($entity_2, $form['entity_2'], $form_state);

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 100,
    );

    return $form;
  }

  /**
   * {@inheritdoc]
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity_1 = $form_state->get('entity_1');
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display_1 */
    $form_display_1 = $form_state->get('form_display_1');
    $form_display_1->extractFormValues($entity_1, $form, $form_state);
    $form_display_1->validateFormValues($entity_1, $form, $form_state);

    $entity_2 = $form_state->get('entity_2');
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display_2 */
    $form_display_2 = $form_state->get('form_display_2');
    $form_display_2->extractFormValues($entity_2, $form['entity_2'], $form_state);
    $form_display_2->validateFormValues($entity_2, $form['entity_2'], $form_state);
  }

  /**
   * {@inheritdoc]
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity_1 */
    $entity_1 = $form_state->get('entity_1');
    $entity_1->save();

    /** @var \Drupal\Core\Entity\EntityInterface $entity_2 */
    $entity_2 = $form_state->get('entity_2');
    $entity_2->save();

    drupal_set_message($this->t('test_entities @id_1 and @id_2 have been updated.', array('@id_1' => $entity_1->id(), '@id_2' => $entity_2->id())));
  }

}
