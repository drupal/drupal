<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test the #group property on #type 'fieldset'.
 */
class FormTestGroupFieldsetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_group_fieldset';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $required = FALSE) {
    $form['fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => 'Fieldset',
      '#required' => !empty($required),
    );
    $form['meta'] = array(
      '#type' => 'container',
      '#title' => 'Group element',
      '#group' => 'fieldset',
    );
    $form['meta']['element'] = array(
      '#type' => 'textfield',
      '#title' => 'Nest in container element',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
