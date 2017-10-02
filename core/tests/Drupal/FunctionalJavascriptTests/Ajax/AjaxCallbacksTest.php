<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests Ajax callbacks on FAPI elements.
 *
 * @group Ajax
 */
class AjaxCallbacksTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_forms_test'];

  /**
   * Tests if Ajax callback works on date element.
   */
  public function testDateAjaxCallback() {

    // Test Ajax callback when date changes.
    $this->drupalGet('ajax_forms_test_ajax_element_form');
    $this->assertSession()->responseContains('No date yet selected');
    $this->getSession()->getPage()->fillField('edit-date', '2016-01-01');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->responseNotContains('No date yet selected');
    $this->assertSession()->responseContains('2016-01-01');
  }

  /**
   * Tests if Ajax callback works on datetime element.
   */
  public function testDateTimeAjaxCallback() {

    // Test Ajax callback when datetime changes.
    $this->drupalGet('ajax_forms_test_ajax_element_form');
    $this->assertSession()->responseContains('No datetime selected.');
    $this->getSession()->getPage()->fillField('edit-datetime-date', '2016-01-01');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->responseNotContains('No datetime selected.');
    $this->assertSession()->responseContains('2016-01-01');
    $this->getSession()->getPage()->fillField('edit-datetime-time', '12:00:00');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->responseContains('2016-01-01 12:00:00');
  }

}
