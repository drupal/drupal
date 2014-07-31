<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestEmptySelectForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test select elements when #options is not an array.
 */
class FormTestEmptySelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_empty_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['empty_select'] = array(
      '#type' => 'select',
      '#title' => t('Empty Select'),
      '#multiple' => FALSE,
      '#options' => NULL,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
