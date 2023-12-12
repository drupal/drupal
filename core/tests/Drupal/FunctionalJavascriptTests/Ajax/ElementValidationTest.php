<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Various tests of AJAX behavior.
 *
 * @group Ajax
 */
class ElementValidationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test', 'ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tries to post an Ajax change to a form that has a validated element.
   *
   * Drupal AJAX commands update the DOM echoing back the validated values in
   * the form of messages that appear on the page.
   */
  public function testAjaxElementValidation() {
    $this->drupalGet('ajax_validation_test');
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    // Partially complete the form with a string.
    $page->fillField('drivertext', 'some dumb text');
    // Move focus away from this field to trigger AJAX.
    $page->findField('spare_required_field')->focus();

    // When the AJAX command updates the DOM a <ul> unsorted list
    // "message__list" structure will appear on the page echoing back the
    // "some dumb text" message.
    $placeholder_text = $assert->waitForElement('css', "[aria-label='Status message'] > ul > li > em:contains('some dumb text')");
    $this->assertNotNull($placeholder_text, 'A callback successfully echoed back a string.');

    $this->drupalGet('ajax_validation_test');
    // Partially complete the form with a number.
    $page->fillField('drivernumber', '12345');
    $page->findField('spare_required_field')->focus();

    // The AJAX request/response will complete successfully when an
    // InsertCommand injects a message with a placeholder element into the DOM
    // with the submitted number.
    $placeholder_number = $assert->waitForElement('css', "[aria-label='Status message'] > ul > li > em:contains('12345')");
    $this->assertNotNull($placeholder_number, 'A callback successfully echoed back a number.');
  }

}
