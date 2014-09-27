<?php

/**
 * @file
 * Contains \Drupal\ajax_test\Form\AjaxTestForm.
 */

namespace Drupal\ajax_test\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Dummy form for testing DialogController with _form routes.
 */
class AjaxTestForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#action'] = \Drupal::url('ajax_test.dialog');

    $form['description'] = array(
      '#markup' => '<p>' . t("Ajax Form contents description.") . '</p>',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Do it'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

}
