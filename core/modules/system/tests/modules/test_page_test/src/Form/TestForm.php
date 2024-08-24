<?php

declare(strict_types=1);

namespace Drupal\test_page_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a test form for testing assertions.
 *
 * @internal
 */
class TestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test_table'] = [
      '#type' => 'table',
      '#header' => ['Column 1', 'Column 2', 'Column 3'],
      'row_1' => [
        'col_1' => ['#plain_text' => 'foo'],
        'col_2' => ['#plain_text' => 'bar'],
        'col_3' => ['#plain_text' => 'baz'],
      ],
      'row_2' => [
        'col_1' => ['#plain_text' => 'one'],
        'col_2' => ['#plain_text' => 'two'],
        'col_3' => ['#plain_text' => 'three'],
      ],
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => 'Name',
      '#default_value' => 'Test name',
    ];

    $form['checkbox_enabled'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox enabled',
      '#default_value' => TRUE,
    ];

    $form['checkbox_disabled'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox disabled',
      '#default_value' => FALSE,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => 'Description',
      '#default_value' => '',
    ];

    $form['options'] = [
      '#type' => 'select',
      '#title' => 'Options',
      '#options' => [
        1 => 'one',
        2 => 'two',
        3 => 'three',
      ],
      '#default_value' => 2,
    ];

    $form['duplicate_button'] = [
      '#type' => 'submit',
      '#name' => 'duplicate_button',
      '#value' => 'Duplicate button 1',
    ];

    $form['duplicate_button_2'] = [
      '#type' => 'submit',
      '#name' => 'duplicate_button',
      '#value' => 'Duplicate button 2',
    ];

    $form['test_textarea_with_newline'] = [
      '#type' => 'textarea',
      '#title' => 'Textarea with newline',
      '#default_value' => "Test text with\nnewline",
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty on purpose, we just want to test the rendered form elements.
  }

}
