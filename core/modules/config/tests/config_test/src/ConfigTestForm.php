<?php

/**
 * @file
 * Contains Drupal\config_test\ConfigTestForm.
 */

namespace Drupal\config_test;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the test config edit forms.
 */
class ConfigTestForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => 'Label',
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => 'config_test_load',
        'replace_pattern' => '[^a-z0-9_.]+',
      ),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => 'Weight',
      '#default_value' => $entity->get('weight'),
    );
    $form['style'] = array(
      '#type' => 'select',
      '#title' => 'Image style',
      '#options' => array(),
      '#default_value' => $entity->get('style'),
      '#access' => FALSE,
    );
    if ($this->moduleHandler->moduleExists('image')) {
      $form['style']['#access'] = TRUE;
      $form['style']['#options'] = image_style_options();
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => 'Delete',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status === SAVED_UPDATED) {
      drupal_set_message(format_string('%label configuration has been updated.', array('%label' => $entity->label())));
    }
    else {
      drupal_set_message(format_string('%label configuration has been created.', array('%label' => $entity->label())));
    }

    $form_state->setRedirect('config_test.list_page');
  }

}
