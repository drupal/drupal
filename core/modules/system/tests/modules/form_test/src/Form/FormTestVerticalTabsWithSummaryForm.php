<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test vertical-tabs form element with tab summaries.
 *
 * @internal
 */
class FormTestVerticalTabsWithSummaryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_vertical_tabs_with_summary_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['information'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-tab1',
    ];
    $form['tab1'] = [
      '#type' => 'details',
      '#title' => $this->t('Tab 1'),
      '#group' => 'information',
    ];
    $form['tab1']['field1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field 1'),
    ];
    $form['tab2'] = [
      '#type' => 'details',
      '#title' => $this->t('Tab 2'),
      '#group' => 'information',
    ];
    $form['tab2']['field2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field 2'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Attach the library that sets the vertical tab summaries.
    $form['#attached']['library'][] = 'form_test/form_test';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('Form submitted.'));
  }

}
