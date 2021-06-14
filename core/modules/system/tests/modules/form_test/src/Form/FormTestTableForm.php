<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form constructor for testing #type 'table' elements.
 *
 * @internal
 */
class FormTestTableForm extends FormTestTableSelectFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table'] = [
      '#type' => 'table',
      '#tableselect' => TRUE,
      '#empty' => $this->t('Empty text.'),
    ];
    $form['table']['row'] = [
      'data' => [
        '#title' => '<em>kitten</em>',
        '#markup' => '<p>some text</p>',
      ],
    ];
    $form['table']['another_row'] = [
      'data' => [
        '#title' => $this->t('my favorite fruit is <strong>@fruit</strong>', ['@fruit' => 'bananas']),
        '#markup' => '<p>some more text</p>',
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
