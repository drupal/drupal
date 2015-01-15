<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\DialogTest.
 */

namespace Drupal\system\Tests\Ajax;

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
    $dialog_renderable = ajax_test_dialog_contents();
    $dialog_contents = drupal_render($dialog_renderable);
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

    // Emulate going to the JS version of the page and check the JSON response.
    $ajax_result = $this->drupalGetAJAX('ajax-test/dialog-contents', array(), array('Accept: application/vnd.drupal-modal'));
    $this->assertEqual($modal_expected_response, $ajax_result[1], 'Modal dialog JSON response matches.');

    // Check that requesting a "normal" dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-contents');
    $this->assertRaw($dialog_contents, 'Non-JS normal dialog page present.');

    // Emulate going to the JS version of the page and check the JSON response.
    // This needs to use WebTestBase::drupalPostAjaxForm() so that the correct
    // dialog options are sent.
    $ajax_result = $this->drupalPostAjaxForm('ajax-test/dialog', array(
        // We have to mock a form element to make drupalPost submit from a link.
        'textfield' => 'test',
      ), array(), 'ajax-test/dialog-contents', array(), array('Accept: application/vnd.drupal-dialog'), NULL, array(
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
      ), array(), 'ajax-test/dialog-contents', array(), array('Accept: application/vnd.drupal-dialog'), NULL, array(
      // Don't send a target.
      'submit' => array()
    ));
    $this->assertEqual($no_target_expected_response, $ajax_result[3], 'Normal dialog with no target JSON response matches.');

    // Emulate closing the dialog via an AJAX request. There is no non-JS
    // version of this test.
    $ajax_result = $this->drupalGetAJAX('ajax-test/dialog-close');
    $this->assertEqual($close_expected_response, $ajax_result[0], 'Close dialog JSON response matches.');

    // Test submitting via a POST request through the button for modals. This
    // approach more accurately reflects the real responses by Drupal because
    // all of the necessary page variables are emulated.
    $ajax_result = $this->drupalPostAjaxForm('ajax-test/dialog', array(), 'button1');

    // Check that CSS and JavaScript are "added" to the page dynamically.
    $this->assertTrue(in_array('dialog.css', array_keys($ajax_result[0]['settings']['ajaxPageState']['css'])), 'jQuery UI dialog CSS added to the page.');
    $this->assertTrue(in_array('core/assets/vendor/jquery.ui/ui/dialog-min.js', array_keys($ajax_result[0]['settings']['ajaxPageState']['js'])), 'jQuery UI dialog JS added to the page.');
    $this->assertTrue(in_array('core/misc/dialog/dialog.ajax.js', array_keys($ajax_result[0]['settings']['ajaxPageState']['js'])), 'Drupal dialog JS added to the page.');

    // Check that the response matches the expected value.
    $this->assertEqual($modal_expected_response, $ajax_result[3], 'POST request modal dialog JSON response matches.');

    // Abbreviated test for "normal" dialogs, testing only the difference.
    $ajax_result = $this->drupalPostAjaxForm('ajax-test/dialog', array(), 'button2');
    $this->assertEqual($normal_expected_response, $ajax_result[3], 'POST request normal dialog JSON response matches.');

    // Check that requesting a form dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-form');
    // Check we get a chunk of the code, we can't test the whole form as form
    // build id and token with be different.
    $form = $this->xpath("//form[@id='ajax-test-form']");
    $this->assertTrue(!empty($form), 'Non-JS form page present.');

    // Emulate going to the JS version of the form and check the JSON response.
    $ajax_result = $this->drupalGetAJAX('ajax-test/dialog-form', array(), array('Accept: application/vnd.drupal-modal'));
    $expected_ajax_settings = [
      'edit-preview' => [
        'callback' => '::preview',
        'event' => 'click',
        'url' => Url::fromRoute('system.ajax')->toString(),
        'accepts' => 'application/vnd.drupal-ajax',
        'submit' => [
          '_triggering_element_name' => 'op',
          '_triggering_element_value' => 'Preview',
        ],
      ],
    ];
    $this->assertEqual($expected_ajax_settings, $ajax_result[0]['settings']['ajax']);
    $this->drupalSetContent($ajax_result[1]['data']);
    // Remove the data, the form build id and token will never match.
    unset($ajax_result[1]['data']);
    $form = $this->xpath("//form[@id='ajax-test-form']");
    $this->assertTrue(!empty($form), 'Modal dialog JSON contains form.');
    $this->assertEqual($form_expected_response, $ajax_result[1]);

    // Check that requesting an entity form dialog without JS goes to a page.
    $this->drupalGet('admin/structure/contact/add');
    // Check we get a chunk of the code, we can't test the whole form as form
    // build id and token with be different.
    $form = $this->xpath("//form[@id='contact-form-add-form']");
    $this->assertTrue(!empty($form), 'Non-JS entity form page present.');

    // Emulate going to the JS version of the form and check the JSON response.
    $ajax_result = $this->drupalGetAJAX('admin/structure/contact/add', array(), array('Accept: application/vnd.drupal-modal'));
    $this->drupalSetContent($ajax_result[1]['data']);
    // Remove the data, the form build id and token will never match.
    unset($ajax_result[1]['data']);
    $form = $this->xpath("//form[@id='contact-form-add-form']");
    $this->assertTrue(!empty($form), 'Modal dialog JSON contains entity form.');
    $this->assertEqual($entity_form_expected_response, $ajax_result[1]);
  }

}
