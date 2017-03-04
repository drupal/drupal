<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test the #group property on #type 'container'.
 */
class FormTestGroupContainerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_group_container';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['container'] = [
      '#type' => 'container',
    ];
    $form['meta'] = [
      '#type' => 'details',
      '#title' => 'Group element',
      '#open' => TRUE,
      '#group' => 'container',
    ];
    $form['meta']['element'] = [
      '#type' => 'textfield',
      '#title' => 'Nest in details element',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
