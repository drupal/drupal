<?php

declare(strict_types=1);

namespace Drupal\ajax_forms_test\Form;

use Drupal\ajax_forms_test\Callbacks;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder: Builds a form that has image button with an ajax callback.
 *
 * @internal
 */
class AjaxFormsTestImageButtonForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_forms_test_image_button_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['image_button'] = [
      '#type' => 'image_button',
      '#name' => 'image_button',
      '#src' => 'core/misc/icons/787878/cog.svg',
      '#attributes' => ['alt' => $this->t('Edit')],
      '#op' => 'edit',
      '#ajax' => [
        'callback' => [Callbacks::class, 'imageButtonCallback'],
      ],
      '#suffix' => '<div id="ajax_image_button_result">Image button not pressed yet.</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit code needed.
  }

}
