<?php

namespace Drupal\ajax_test\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for testing AJAX MessageCommand.
 *
 * @internal
 */
class AjaxTestMessageCommandForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_message_command_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['alternate-message-container'] = [
      '#type' => 'container',
      '#id' => 'alternate-message-container',
    ];
    $form['button_default'] = [
      '#type' => 'submit',
      '#name' => 'make_default_message',
      '#value' => 'Make Message In Default Location',
      '#ajax' => [
        'callback' => '::makeMessageDefault',
      ],
    ];
    $form['button_alternate'] = [
      '#type' => 'submit',
      '#name' => 'make_alternate_message',
      '#value' => 'Make Message In Alternate Location',
      '#ajax' => [
        'callback' => '::makeMessageAlternate',
      ],
    ];
    $form['button_warning'] = [
      '#type' => 'submit',
      '#name' => 'make_warning_message',
      '#value' => 'Make Warning Message',
      '#ajax' => [
        'callback' => '::makeMessageWarning',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Callback for testing MessageCommand with default settings.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function makeMessageDefault() {
    $response = new AjaxResponse();
    return $response->addCommand(new MessageCommand('I am a message in the default location.'));
  }

  /**
   * Callback for testing MessageCommand using an alternate message location.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function makeMessageAlternate() {
    $response = new AjaxResponse();
    return $response->addCommand(new MessageCommand('I am a message in an alternate location.', '#alternate-message-container', [], FALSE));
  }

  /**
   * Callback for testing MessageCommand with warning status.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function makeMessageWarning() {
    $response = new AjaxResponse();
    return $response->addCommand(new MessageCommand('I am a warning message in the default location.', NULL, ['type' => 'warning', 'announce' => '']));
  }

}
