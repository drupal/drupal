<?php

declare(strict_types=1);

namespace Drupal\ajax_test\Form;

use Drupal\ajax_test\Ajax\AjaxTestCommandReturnPromise;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Test form for ajax_test_form_promise.
 *
 * @internal
 */
class AjaxTestFormPromise extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_form_promise';
  }

  /**
   * Form for testing the addition of various types of elements via Ajax.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'ajax_test/command_promise';
    $form['custom']['#prefix'] = '<div id="ajax_test_form_promise_wrapper">';
    $form['custom']['#suffix'] = '</div>';

    // Button to test for the execution order of Ajax commands.
    $form['test_execution_order_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute commands button'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [static::class, 'executeCommands'],
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'ajax_test_form_promise_wrapper',
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback for the "Execute commands button" button.
   */
  public static function executeCommands(array $form, FormStateInterface $form_state) {
    $selector = '#ajax_test_form_promise_wrapper';
    $response = new AjaxResponse();

    $response->addCommand(new AppendCommand($selector, '1'));
    $response->addCommand(new AjaxTestCommandReturnPromise($selector, '2'));
    $response->addCommand(new AppendCommand($selector, '3'));
    $response->addCommand(new AppendCommand($selector, '4'));
    $response->addCommand(new AjaxTestCommandReturnPromise($selector, '5'));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // An empty implementation, as we never submit the actual form.
  }

}
