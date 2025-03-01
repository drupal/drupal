<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test the submit_button attribute.
 *
 * @internal
 */
class FormTestSubmitButtonAttributeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_test_submit_button_attribute';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $customize = FALSE): array {
    $form['submit-button-attr'] = [
      '#type' => 'button',
      '#submit_button' => TRUE,
      '#value' => $this->t('Try to Submit'),
    ];

    if ($customize) {
      $form['submit-button-attr'] = [
        '#type' => 'button',
        '#submit_button' => FALSE,
        '#value' => $this->t('Submit if you can'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
