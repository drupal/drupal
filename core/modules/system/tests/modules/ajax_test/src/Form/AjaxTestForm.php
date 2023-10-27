<?php

namespace Drupal\ajax_test\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Dummy form for testing DialogRenderer with _form routes.
 *
 * @internal
 */
class AjaxTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#action'] = Url::fromRoute('ajax_test.dialog')->toString();

    $form['description'] = [
      '#markup' => '<p>' . $this->t("Ajax Form contents description.") . '</p>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Do it'),
    ];
    $form['actions']['preview'] = [
      '#title' => 'Preview',
      '#type' => 'link',
      '#url' => Url::fromRoute('ajax_test.dialog_form'),
      '#attributes' => [
        'class' => ['use-ajax', 'button'],
        'data-dialog-type' => 'modal',
      ],
    ];
    $form['actions']['hello_world'] = [
      '#type' => 'submit',
      '#value' => $this->t('Hello world'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::helloWorld',
        'event' => 'click',
      ],
    ];
    return $form;
  }

  /**
   * An AJAX callback that prints "Hello World!" as a message.
   *
   * @param array $form
   *   The form array to remove elements from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function helloWorld(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new MessageCommand('Hello world!'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

}
