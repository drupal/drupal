<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for testing preservation of values during a rebuild.
 */
class FormTestRebuildPreserveValuesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_rebuild_preserve_values_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start the form with two checkboxes, to test different defaults, and a
    // textfield, to test more than one element type.
    $form = [
      'checkbox_1_default_off' => [
        '#type' => 'checkbox',
        '#title' => t('This checkbox defaults to unchecked'),
        '#default_value' => FALSE,
      ],
      'checkbox_1_default_on' => [
        '#type' => 'checkbox',
        '#title' => t('This checkbox defaults to checked'),
        '#default_value' => TRUE,
      ],
      'text_1' => [
        '#type' => 'textfield',
        '#title' => t('This textfield has a non-empty default value.'),
        '#default_value' => 'DEFAULT 1',
      ],
    ];
    // Provide an 'add more' button that rebuilds the form with an additional two
    // checkboxes and a textfield. The test is to make sure that the rebuild
    // triggered by this button preserves the user input values for the initial
    // elements and initializes the new elements with the correct default values.
    if (!$form_state->has('add_more')) {
      $form['add_more'] = [
        '#type' => 'submit',
        '#value' => 'Add more',
        '#submit' => ['::addMoreSubmitForm'],
      ];
    }
    else {
      $form += [
        'checkbox_2_default_off' => [
          '#type' => 'checkbox',
          '#title' => t('This checkbox defaults to unchecked'),
          '#default_value' => FALSE,
        ],
        'checkbox_2_default_on' => [
          '#type' => 'checkbox',
          '#title' => t('This checkbox defaults to checked'),
          '#default_value' => TRUE,
        ],
        'text_2' => [
          '#type' => 'textfield',
          '#title' => t('This textfield has a non-empty default value.'),
          '#default_value' => 'DEFAULT 2',
        ],
      ];
    }
    // A submit button that finishes the form workflow (does not rebuild).
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addMoreSubmitForm(array &$form, FormStateInterface $form_state) {
    // Rebuild, to test preservation of input values.
    $form_state->set('add_more', TRUE);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Finish the workflow. Do not rebuild.
    drupal_set_message(t('Form values: %values', ['%values' => var_export($form_state->getValues(), TRUE)]));
  }

}
