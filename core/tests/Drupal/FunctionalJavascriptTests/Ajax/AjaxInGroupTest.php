<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that form elements in groups work correctly with AJAX.
 *
 * @group Ajax
 */
class AjaxInGroupTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['access content']));
  }

  /**
   * Submits forms with select and checkbox elements via Ajax.
   */
  public function testSimpleAjaxFormValue() {
    $this->drupalGet('/ajax_forms_test_get_form');

    $assert_session = $this->assertSession();
    $assert_session->responseContains('Test group');
    $assert_session->responseContains('AJAX checkbox in a group');

    $session = $this->getSession();
    $checkbox_original = $session->getPage()->findField('checkbox_in_group');
    $this->assertNotNull($checkbox_original, 'The checkbox_in_group is on the page.');
    $original_id = $checkbox_original->getAttribute('id');

    // Triggers an AJAX request/response.
    $checkbox_original->check();

    // The response contains a new nested "test group" form element, similar
    // to the one already in the DOM except for a change in the form build id.
    $checkbox_new = $assert_session->waitForElement('xpath', "//input[@name='checkbox_in_group' and not(@id='$original_id')]");
    $this->assertNotNull($checkbox_new, 'DOM update: clicking the checkbox refreshed the checkbox_in_group structure');

    $assert_session->responseContains('Test group');
    $assert_session->responseContains('AJAX checkbox in a group');
    $assert_session->responseContains('AJAX checkbox in a nested group');
    $assert_session->responseContains('Another AJAX checkbox in a nested group');
  }

}
