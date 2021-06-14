<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test the #optional property on #type 'container'.
 *
 * @internal
 */
class FormTestOptionalContainerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_optional_container';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Empty containers.
    $form['empty_optional'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['empty_optional']],
      '#optional' => TRUE,
    ];
    $form['empty_nonoptional'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['empty_nonoptional']],
      '#optional' => FALSE,
    ];

    // Non-empty containers
    $form['nonempty_optional'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['nonempty_optional']],
      '#optional' => TRUE,
    ];
    $form['nonempty_optional']['child_1'] = [];

    $form['nonempty_nonoptional'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['nonempty_nonoptional']],
      '#optional' => FALSE,
    ];
    $form['nonempty_nonoptional']['child_2'] = [];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
