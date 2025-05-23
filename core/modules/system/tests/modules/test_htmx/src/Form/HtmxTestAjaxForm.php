<?php

declare(strict_types=1);

namespace Drupal\test_htmx\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\test_htmx\Controller\HtmxTestAttachmentsController;

/**
 * A small form used to insert an HTMX powered element using ajax API.
 */
class HtmxTestAjaxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'htmx_test_ajax_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $build = [
      'ajax-button' => [
        '#type' => 'button',
        '#value' => 'Trigger Ajax',
        '#submit_button' => FALSE,
        '#ajax' => [
          'callback' => [
            HtmxTestAttachmentsController::class,
            'generateHtmxButton',
          ],
          'wrapper' => 'ajax-test-container',
        ],
      ],
      '#suffix' => '<div id="ajax-test-container"></div>',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
