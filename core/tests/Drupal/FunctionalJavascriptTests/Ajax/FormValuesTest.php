<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that form values are properly delivered to AJAX callbacks.
 *
 * @group Ajax
 */
class FormValuesTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'ajax_test', 'ajax_forms_test'];

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

    $this->drupalGet('ajax_forms_test_get_form');

    $session = $this->getSession();
    $assertSession = $this->assertSession();

    // Verify form values of a select element.
    foreach (['green', 'blue', 'red'] as $item) {
      // Updating the field will trigger an AJAX request/response.
      $session->getPage()->selectFieldOption('select', $item);

      // The AJAX command in the response will update the DOM
      $select = $assertSession->waitForElement('css', "div#ajax_selected_color:contains('$item')");
      $this->assertNotNull($select, "DataCommand has updated the page with a value of $item.");
    }

    // Verify form values of a checkbox element.
    $session->getPage()->checkField('checkbox');
    $div0 = $this->assertSession()->waitForElement('css', "div#ajax_checkbox_value:contains('checked')");
    $this->assertNotNull($div0, 'DataCommand updates the DOM as expected when a checkbox is selected');

    $session->getPage()->uncheckField('checkbox');
    $div1 = $this->assertSession()->waitForElement('css', "div#ajax_checkbox_value:contains('unchecked')");
    $this->assertNotNull($div1, 'DataCommand updates the DOM as expected when a checkbox is de-selected');

    // Verify that AJAX elements with invalid callbacks return error code 500.
    // Ensure the test error log is empty before these tests.
    $this->assertFileDoesNotExist(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log');
    // We don't need to check for the X-Drupal-Ajax-Token header with these
    // invalid requests.
    $this->assertAjaxHeader = FALSE;
    foreach (['null', 'empty', 'nonexistent'] as $key) {
      $element_name = 'select_' . $key . '_callback';
      // Updating the field will trigger an AJAX request/response.
      $session->getPage()->selectFieldOption($element_name, 'green');

      // The select element is disabled as the AJAX request is issued.
      $this->assertSession()->waitForElement('css', "select[name=\"$element_name\"]:disabled");

      // The select element is enabled as the response is received.
      $this->assertSession()->waitForElement('css', "select[name=\"$element_name\"]:enabled");
      $this->assertFileExists(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log');
      $this->assertStringContainsString('"The specified #ajax callback is empty or not callable."', file_get_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log'));
      // The exceptions are expected. Do not interpret them as a test failure.
      // Not using File API; a potential error must trigger a PHP warning.
      unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
    }
    // We need to reload the page to kill any unfinished AJAX calls before
    // tearDown() is called.
    $this->drupalGet('ajax_forms_test_get_form');
  }

}
