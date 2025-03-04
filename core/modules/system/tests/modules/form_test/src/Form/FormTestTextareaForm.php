<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form for testing textarea.
 *
 * @internal
 */
class FormTestTextareaForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_test_textarea_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    foreach (['vertical', 'horizontal', 'both', 'none'] as $resizableMode) {
      $id = 'textarea_resizable_' . $resizableMode;
      $form[$id] = [
        '#type' => 'textarea',
        '#title' => $id,
        '#resizable' => $resizableMode,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
