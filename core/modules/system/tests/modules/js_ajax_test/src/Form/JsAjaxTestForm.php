<?php

namespace Drupal\js_ajax_test\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\js_ajax_test\Ajax\JsAjaxTestCommand;

/**
 * Test form for js_ajax_test
 *
 * @internal
 */
class JsAjaxTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'js_ajax_test_form';
  }

  /**
   * Form for testing the addition of various types of elements via Ajax.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'js_ajax_test/ajax';
    $form['custom']['#prefix'] = '<div id="js_ajax_test_form_wrapper">';
    $form['custom']['#suffix'] = '</div>';

    // Button to test for the waitForButton() assertion.
    $form['test_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add button'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [static::class, 'addButton'],
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'js_ajax_test_form_wrapper',
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback for the "Add button" button.
   */
  public static function addButton(array $form, FormStateInterface $form_state) {
    return (new AjaxResponse())
      ->addCommand(new JsAjaxTestCommand());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // An empty implementation, as we never submit the actual form.
  }

}
