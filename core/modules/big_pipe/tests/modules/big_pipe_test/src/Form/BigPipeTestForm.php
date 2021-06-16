<?php

namespace Drupal\big_pipe_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to test BigPipe.
 *
 * @internal
 */
class BigPipeTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'big_pipe_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#token'] = FALSE;

    $form['big_pipe'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('BigPipe works…'),
      '#options' => [
        'js' => $this->t('… with JavaScript'),
        'nojs' => $this->t('… without JavaScript'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
