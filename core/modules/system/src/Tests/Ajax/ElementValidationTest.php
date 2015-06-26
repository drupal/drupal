<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Ajax\ElementValidationTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Various tests of AJAX behavior.
 *
 * @group Ajax
 */
class ElementValidationTest extends AjaxTestBase {
  /**
   * Tries to post an Ajax change to a form that has a validated element.
   *
   * The drivertext field is Ajax-enabled. An additional field is not, but
   * is set to be a required field. In this test the required field is not
   * filled in, and we want to see if the activation of the "drivertext"
   * Ajax-enabled field fails due to the required field being empty.
   */
  function testAjaxElementValidation() {
    $edit = array('drivertext' => t('some dumb text'));

    // Post with 'drivertext' as the triggering element.
    $this->drupalPostAjaxForm('ajax_validation_test', $edit, 'drivertext');
    // Look for a validation failure in the resultant JSON.
    $this->assertNoText(t('Error message'), 'No error message in resultant JSON');
    $this->assertText('ajax_forms_test_validation_form_callback invoked', 'The correct callback was invoked');

    $this->drupalGet('ajax_validation_test');
    $edit = array('drivernumber' => 12345);

    // Post with 'drivernumber' as the triggering element.
    $this->drupalPostAjaxForm('ajax_validation_test', $edit, 'drivernumber');
    // Look for a validation failure in the resultant JSON.
    $this->assertNoText(t('Error message'), 'No error message in resultant JSON');
    $this->assertText('ajax_forms_test_validation_number_form_callback invoked', 'The correct callback was invoked');
  }
}
