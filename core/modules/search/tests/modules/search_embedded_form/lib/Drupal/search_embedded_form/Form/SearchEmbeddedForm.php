<?php

/**
 * @file
 * Contains \Drupal\search_embedded_form\Form\SearchEmbeddedForm.
 */

namespace Drupal\search_embedded_form\Form;

use Drupal\Core\Form\FormBase;

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
  public function buildForm(array $form, array &$form_state) {
    $count = $this->config('search_embedded_form.settings')->get('submitted');

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
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('search_embedded_form.settings');
    $submit_count = (int) $config->get('submitted');
    $config->set('submitted', $submit_count + 1)->save();
    drupal_set_message($this->t('Test form was submitted'));
  }

}
