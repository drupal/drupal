<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestRedirectForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;

/**
 * Form builder to detect form redirect.
 */
class FormTestRedirectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_redirect';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['redirection'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use redirection'),
    );
    $form['destination'] = array(
      '#type' => 'textfield',
      '#title' => t('Redirect destination'),
      '#states' => array(
        'visible' => array(
          ':input[name="redirection"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if (!empty($form_state['values']['redirection'])) {
      $form_state['redirect'] = !empty($form_state['values']['destination']) ? $form_state['values']['destination'] : NULL;
    }
    else {
      $form_state['redirect'] = FALSE;
    }
  }

}
