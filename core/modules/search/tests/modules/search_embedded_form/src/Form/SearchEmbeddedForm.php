<?php

/**
 * @file
 * Contains \Drupal\search_embedded_form\Form\SearchEmbeddedForm.
 */

namespace Drupal\search_embedded_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for search_embedded_form form.
 */
class SearchEmbeddedForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_embedded_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $count = \Drupal::state()->get('search_embedded_form.submit_count');

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#required' => TRUE,
      '#description' => $this->t('Times form has been submitted: %count', array('%count' => $count)),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send away'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $submit_count = (int) $state->get('search_embedded_form.submit_count');
    $state->set('search_embedded_form.submit_count', $submit_count + 1);
    drupal_set_message($this->t('Test form was submitted'));
  }

}
