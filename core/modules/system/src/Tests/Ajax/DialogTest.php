<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Ajax\DialogTest.
 */

namespace Drupal\system\Tests\Ajax;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

/**
 * Performs tests on opening and manipulating dialogs via AJAX commands.
 *
 * @group Ajax
 */
class DialogTest extends AjaxTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('ajax_test', 'ajax_forms_test', 'contact');

  /**
   * Test sending non-JS and AJAX requests to open and manipulate modals.
   */
  public function testDialog() {
    $this->drupalLogin($this->drupalCreateUser(array('administer contact forms')));
    // Ensure the elements render without notices or exceptions.
    $this->drupalGet('ajax-test/dialog');

    // Set up variables for this test.
    $dialog_renderable = \Drupal\ajax_test\Controller\AjaxTestController::dialogContents();
    $dialog_contents = \Drupal::service('renderer')->renderRoot($dialog_renderable);
    $modal_expected_response = array(
      'command' => 'openDialog',
      'selector' => '#drupal-modal',
      'settings' => NULL,
      'data' => $dialog_contents,
      'dialogOptions' => array(
        'modal' => TRUE,
        'title' => 'AJAX Dialog contents',
      ),
    );
    $form_expected_response = array(
      'command' => 'openDialog',
      'selector' => '#drupal-modal',
      'settings' => NULL,
      'dialogOptions' => array(
        'modal' => TRUE,
        'title' => 'Ajax Form contents',
      ),
    );
    $entity_form_expected_response = array(
      'command' => 'openDialog',
      'selector' => '#drupal-modal',
      'settings' => NULL,
      'dialogOptions' => array(
        'modal' => TRUE,
        'title' => 'Add contact form',
      ),
    );
    $normal_expected_response = array(
      'command' => 'openDialog',
      'selector' => '#ajax-test-dialog-wrapper-1',
      'settings' => NULL,
      'data' => $dialog_contents,
      'dialogOptions' => array(
        'modal' => FALSE,
        'title' => 'AJAX Dialog contents',
      ),
    );
    $no_target_expected_response = array(
      'command' => 'openDialog',
      'selector' => '#drupal-dialog-ajax-testdialog-contents',
      'settings' => NULL,
      'data' => $dialog_contents,
      'dialogOptions' => array(
        'modal' => FALSE,
        'title' => 'AJAX Dialog contents',
      ),
    );
    $close_expected_response = array(
      'command' => 'closeDialog',
      'selector' => '#ajax-test-dialog-wrapper-1',
      'persist' => FALSE,
    );

    // Check that requesting a modal dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-contents');
    $this->assertRaw($dialog_contents, 'Non-JS modal dialog page present.');

    // Check that requesting a modal dialog with XMLHttpRequest goes to a page.
    $this->drupalGetXHR('ajax-test/dialog-contents');
    $this->assertRaw($dialog_contents, 'Modal dialog page on XMLHttpRequest present.');

    // Emulate going to the JS version of the page and check the JSON response.
    $ajax_result = $this->drupalGetAjax('ajax-test/dialog-contents', array('query' => array(MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal')));
    $this->assertEqual($modal_expected_response, $ajax_result[3], 'Modal dialog JSON response matches.');

    // Check that requesting a "normal" dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-contents');
    $this->assertRaw($dialog_contents, 'Non-JS normal dialog page present.');

    // Emulate going to the JS version of the page and check the JSON response.
    // This needs to use WebTestBase::drupalPostAjaxForm() so that the correct
    // dialog options are sent.
    $ajax_result = $this->drupalPostAjaxForm('ajax-test/dialog', array(
        // We have to mock a form element to make drupalPost submit from a link.
        'textfield' => 'test',
      ), array(), 'ajax-test/dialog-contents', array('query' => array(MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_dialog')), array(), NULL, array(
      'submit' => array(
        'dialogOptions[target]' => 'ajax-test-dialog-wrapper-1',
      )
    ));
    $this->assertEqual($normal_expected_response, $ajax_result[3], 'Normal dialog JSON response matches.');

    // Emulate going to the JS version of the page and check the JSON response.
    // This needs to use WebTestBase::drupalPostAjaxForm() so that the correct
    // dialog options are sent.
    $ajax_result = $this->drupalPostAjaxForm('ajax-test/dialog', array(
        // We have to mock a form element to make drupalPost submit from a link.
        'textfield' => 'test',
      ), array(), 'ajax-test/dialog-contents', array('query' => array(MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_dialog')), array(), NULL, array(
      // Don't send a target.
      'submit' => array()
    ));
    // Make sure the selector ID starts with the right string.
    $this->assert(strpos($ajax_result[3]['selector'], $no_target_expected_response['selector']) === 0, 'Selector starts with right string.');
    unset($ajax_result[3]['selector']);
    unset($no_target_expected_response['selector']);
    $this->assertEqual($no_target_expected_response, $ajax_result[3], 'Normal dialog with no target JSON response matches.');

    // Emulate closing the dialog via an AJAX request. There is no non-JS
    // version of this test.
    $ajax_result = $this->drupalGetAjax('ajax-test/dialog-close');
    $this->assertEqual($close_expected_response, $ajax_result[0], 'Close dialog JSON response matches.');

    // Test submitting via a POST request through the button for modals. This
    // approach more accurately reflects the real responses by Drupal because
    // all of the necessary page variables are emulated.
    $ajax_result = $this->drupalPostAjaxForm('ajax-test/dialog', array(), 'button1');

    // Check that CSS and JavaScript are "added" to the page dynamically.
    $this->assertTrue(in_array('core/drupal.dialog.ajax', explode(',', $ajax_result[0]['settings']['ajaxPageState']['libraries'])), 'core/drupal.dialog.ajax library is added to the page.');
    $dialog_css_exists = strpos($ajax_result[1]['data'], 'dialog.css') !== FALSE;
    $this->assertTrue($dialog_css_exists, 'jQuery UI dialog CSS added to the page.');
    $dialog_js_exists = strpos($ajax_result[2]['data'], 'dialog-min.js') !== FALSE;
    $this->assertTrue($dialog_js_exists, 'jQuery UI dialog JS added to the page.');
    $dialog_js_exists = strpos($ajax_result[2]['data'], 'dialog.ajax.js') !== FALSE;
    $this->assertTrue($dialog_js_exists, 'Drupal dialog JS added to the page.');

    // Check that the response matches the expected value.
    $this->assertEqual($modal_expected_response, $ajax_result[4], 'POST request modal dialog JSON response matches.');

    // Abbreviated test for "normal" dialogs, testing only the difference.
    $ajax_result = $this->drupalPostAjaxForm('ajax-test/dialog', array(), 'button2');
    $this->assertEqual($normal_expected_response, $ajax_result[4], 'POST request normal dialog JSON response matches.');

    // Check that requesting a form dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-form');
    // Check we get a chunk of the code, we can't test the whole form as form
    // build id and token with be different.
    $form = $this->xpath("//form[@id='ajax-test-form']");
    $this->assertTrue(!empty($form), 'Non-JS form page present.');

    // Emulate going to the JS version of the form and check the JSON response.
    $ajax_result = $this->drupalGetAjax('ajax-test/dialog-form', array('query' => array(MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal')));
    $expected_ajax_settings = [
      'edit-preview' => [
        'callback' => '::preview',
        'event' => 'click',
        'url' => Url::fromRoute('ajax_test.dialog_form', [], ['query' => [
            MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal',
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ]])->toString(),
        'dialogType' => 'ajax',
        'submit' => [
          '_triggering_element_name' => 'op',
          '_triggering_element_value' => 'Preview',
        ],
      ],
    ];
    $this->assertEqual($expected_ajax_settings, $ajax_result[0]['settings']['ajax']);
    $this->setRawContent($ajax_result[3]['data']);
    // Remove the data, the form build id and token will never match.
    unset($ajax_result[3]['data']);
    $form = $this->xpath("//form[@id='ajax-test-form']");
    $this->assertTrue(!empty($form), 'Modal dialog JSON contains form.');
    $this->assertEqual($form_expected_response, $ajax_result[3]);

    // Check that requesting an entity form dialog without JS goes to a page.
    $this->drupalGet('admin/structure/contact/add');
    // Check we get a chunk of the code, we can't test the whole form as form
    // build id and token with be different.
    $form = $this->xpath("//form[@id='contact-form-add-form']");
    $this->assertTrue(!empty($form), 'Non-JS entity form page present.');

    // Emulate going to the JS version of the form and check the JSON response.
    $ajax_result = $this->drupalGetAjax('admin/structure/contact/add', array('query' => array(MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal')));
    $this->setRawContent($ajax_result[3]['data']);
    // Remove the data, the form build id and token will never match.
    unset($ajax_result[3]['data']);
    $form = $this->xpath("//form[@id='contact-form-add-form']");
    $this->assertTrue(!empty($form), 'Modal dialog JSON contains entity form.');
    $this->assertEqual($entity_form_expected_response, $ajax_result[3]);
  }

}
