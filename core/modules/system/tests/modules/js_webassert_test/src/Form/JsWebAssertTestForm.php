<?php

namespace Drupal\js_webassert_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Test form for JSWebAssert JavaScriptTestBase.
 *
 * @internal
 */
class JsWebAssertTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'js_webassert_test_form';
  }

  /**
   * Form for testing the addition of various types of elements via AJAX.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="js_webassert_test_form_wrapper">';
    $form['#suffix'] = '</div>';

    // Button to test for the waitForButton() assertion.
    $form['test_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add button'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => 'Drupal\js_webassert_test\Form\JsWebAssertTestForm::addButton',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'js_webassert_test_form_wrapper',
      ],
    ];
    // Button to test for the waitForLink() assertion.
    $form['test_link'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add link'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => 'Drupal\js_webassert_test\Form\JsWebAssertTestForm::addLink',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'js_webassert_test_form_wrapper',
      ],
    ];
    // Button to test for the waitForField() assertion.
    $form['test_field'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add field'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => 'Drupal\js_webassert_test\Form\JsWebAssertTestForm::addField',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'js_webassert_test_form_wrapper',
      ],
    ];
    // Button to test for the waitForId() assertion.
    $form['test_id'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add ID'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => 'Drupal\js_webassert_test\Form\JsWebAssertTestForm::addId',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'js_webassert_test_form_wrapper',
      ],
    ];

    // Button to test the assertWaitOnAjaxRequest() assertion.
    $form['test_wait_for_element_visible'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test waitForElementVisible'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => 'Drupal\js_webassert_test\Form\JsWebAssertTestForm::addWaitForElementVisible',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'js_webassert_test_form_wrapper',
      ],
    ];

    // Button to test the assertWaitOnAjaxRequest() assertion.
    $form['test_assert_wait_on_ajax_request'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test assertWaitOnAjaxRequest'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => 'Drupal\js_webassert_test\Form\JsWebAssertTestForm::addAssertWaitOnAjaxRequest',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
        'wrapper' => 'js_webassert_test_form_wrapper',
      ],
    ];

    // Button to test the assertNoElementAfterWait() assertion, will pass.
    $form['test_assert_no_element_after_wait_pass'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test assertNoElementAfterWait: pass'),
      '#button_type' => 'primary',
      '#attached' => ['library' => 'js_webassert_test/no_element_after_wait'],
    ];

    // Button to test the assertNoElementAfterWait() assertion, will fail.
    $form['test_assert_no_element_after_wait_fail'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test assertNoElementAfterWait: fail'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Ajax callback for the "Add button" button.
   */
  public static function addButton(array $form, FormStateInterface $form_state) {
    $form['added_button'] = [
      '#type' => 'submit',
      '#value' => 'Added button',
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * Ajax callback for the "Add link" button.
   */
  public static function addLink(array $form, FormStateInterface $form_state) {
    $form['added_link'] = [
      '#title' => 'Added link',
      '#type' => 'link',
      '#url' => Url::fromRoute('js_webassert_test.js_webassert_test_form'),
    ];
    return $form;
  }

  /**
   * Ajax callback for the "Add field" button.
   */
  public static function addField(array $form, FormStateInterface $form_state) {
    $form['added_field'] = [
      '#type' => 'textfield',
      '#title' => 'Added textfield',
      '#name' => 'added_field',
    ];
    return $form;
  }

  /**
   * Ajax callback for the "Add ID" button.
   */
  public static function addId(array $form, FormStateInterface $form_state) {
    $form['added_id'] = [
      '#id' => 'js_webassert_test_field_id',
      '#type' => 'submit',
      '#value' => 'Added ID',
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * Ajax callback for the "Test waitForAjax" button.
   */
  public static function addAssertWaitOnAjaxRequest(array $form, FormStateInterface $form_state) {
    // Attach the library necessary for this test.
    $form['#attached']['library'][] = 'js_webassert_test/wait_for_ajax_request';

    $form['test_assert_wait_on_ajax_input'] = [
      '#type' => 'textfield',
      '#name' => 'test_assert_wait_on_ajax_input',
    ];

    return $form;
  }

  /**
   * Ajax callback for the "Test waitForElementVisible" button.
   */
  public static function addWaitForElementVisible(array $form, FormStateInterface $form_state) {
    // Attach the library necessary for this test.
    $form['#attached']['library'][] = 'js_webassert_test/wait_for_element';

    $form['element_invisible'] = [
      '#id' => 'js_webassert_test_element_invisible',
      '#type' => 'submit',
      '#value' => 'Added WaitForElementVisible',
      '#button_type' => 'primary',
      '#attributes' => [
        'style' => ['display: none;'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
