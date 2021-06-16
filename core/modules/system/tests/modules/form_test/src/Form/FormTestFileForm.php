<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form constructor for testing #type 'file' elements.
 *
 * @internal
 */
class FormTestFileForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_file';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#type' => 'file',
      '#multiple' => TRUE,
      '#attributes' => [
        'class' => ['cagatio'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
