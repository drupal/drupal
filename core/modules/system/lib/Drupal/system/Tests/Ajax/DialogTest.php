<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\DialogTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Tests use of dialogs as wrappers for Ajax responses.
 */
class DialogTest extends AjaxTestBase {
  public static function getInfo() {
    return array(
      'name' => 'AJAX dialogs commands',
      'description' => 'Performs tests on opening and manipulating dialogs via AJAX commands.',
      'group' => 'AJAX',
    );
  }

  /**
   * Test sending non-JS and AJAX requests to open and manipulate modals.
   */
  function testDialog() {
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
        'modal' => true,
        'title' => 'AJAX Dialog',
      ),
    );
    $normal_expected_response = array(
      'command' => 'openDialog',
      'selector' => '#ajax-test-dialog-wrapper-1',
      'settings' => NULL,
      'data' => $dialog_contents,
      'dialogOptions' => array(
        'modal' => false,
        'title' => 'AJAX Dialog',
      ),
    );
    $close_expected_response = array(
      'command' => 'closeDialog',
      'selector' => '#ajax-test-dialog-wrapper-1',
    );

    // Check that requesting a modal dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-contents/nojs/1');
    $this->assertRaw($dialog_contents, 'Non-JS modal dialog page present.');

    // Emulate going to the JS version of the page and check the JSON response.
    $ajax_result = $this->drupalGetAJAX('ajax-test/dialog-contents/ajax/1');
    $this->assertEqual($modal_expected_response, $ajax_result[1], 'Modal dialog JSON response matches.');

    // Check that requesting a "normal" dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-contents/nojs');
    $this->assertRaw($dialog_contents, 'Non-JS normal dialog page present.');

    // Emulate going to the JS version of the page and check the JSON response.
    $ajax_result = $this->drupalGetAJAX('ajax-test/dialog-contents/ajax');
    $this->assertEqual($normal_expected_response, $ajax_result[1], 'Normal dialog JSON response matches.');

    // Emulate closing the dialog via an AJAX request. There is no non-JS
    // version of this test.
    $ajax_result = $this->drupalGetAJAX('ajax-test/dialog-close');
    $this->assertEqual($close_expected_response, $ajax_result[0], 'Close dialog JSON response matches.');

    // Test submitting via a POST request through the button for modals. This
    // approach more accurately reflects the real responses by Drupal because
    // all of the necessary page variables are emulated.
    $ajax_result = $this->drupalPostAJAX('ajax-test/dialog', array(), 'button1');

    // Check that CSS and JavaScript are "added" to the page dynamically.
    $dialog_css_exists = strpos($ajax_result[1]['data'], 'jquery.ui.dialog.css') !== FALSE;
    $this->assertTrue($dialog_css_exists, 'jQuery UI dialog CSS added to the page.');
    $dialog_js_exists = strpos($ajax_result[2]['data'], 'jquery.ui.dialog.js') !== FALSE;
    $this->assertTrue($dialog_css_exists, 'jQuery UI dialog JS added to the page.');
    $dialog_js_exists = strpos($ajax_result[2]['data'], 'dialog.ajax.js') !== FALSE;
    $this->assertTrue($dialog_css_exists, 'Drupal dialog JS added to the page.');

    // Check that the response matches the expected value.
    $this->assertEqual($modal_expected_response, $ajax_result[3], 'POST request modal dialog JSON response matches.');

    // Abbreviated test for "normal" dialogs, testing only the difference.
    $ajax_result = $this->drupalPostAJAX('ajax-test/dialog', array(), 'button2');
    $this->assertEqual($normal_expected_response, $ajax_result[3], 'POST request normal dialog JSON response matches.');
  }

}
