<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\ElementValidationTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Miscellaneous Ajax tests using ajax_test module.
 */
class ElementValidationTest extends AjaxTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Miscellaneous AJAX tests',
      'description' => 'Various tests of AJAX behavior',
      'group' => 'AJAX',
    );
  }

  /**
   * Try to post an Ajax change to a form that has a validated element.
   *
   * The drivertext field is Ajax-enabled. An additional field is not, but
   * is set to be a required field. In this test the required field is not
   * filled in, and we want to see if the activation of the "drivertext"
   * Ajax-enabled field fails due to the required field being empty.
   */
  function testAjaxElementValidation() {
    $web_user = $this->drupalCreateUser();
    $edit = array('drivertext' => t('some dumb text'));

    // Post with 'drivertext' as the triggering element.
    $post_result = $this->drupalPostAJAX('ajax_validation_test', $edit, 'drivertext');
    // Look for a validation failure in the resultant JSON.
    $this->assertNoText(t('Error message'), t("No error message in resultant JSON"));
    $this->assertText('ajax_forms_test_validation_form_callback invoked', t('The correct callback was invoked'));
  }
}
