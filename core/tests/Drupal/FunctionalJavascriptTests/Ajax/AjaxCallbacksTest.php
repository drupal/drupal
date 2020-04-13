<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Ajax callbacks on FAPI elements.
 *
 * @group Ajax
 */
class AjaxCallbacksTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if Ajax callback works on date element.
   */
  public function testDateAjaxCallback() {

    // Test Ajax callback when date changes.
    $this->drupalGet('ajax_forms_test_ajax_element_form');
    $this->assertNotEmpty($this->getSession()->getPage()->find('xpath', '//div[@id="ajax_date_value"][text()="No date yet selected"]'));
    $this->getSession()->executeScript('jQuery("[data-drupal-selector=edit-date]").val("2016-01-01").trigger("change");');
    $this->assertNotEmpty($this->assertSession()->waitForElement('xpath', '//div[@id="ajax_date_value"]/div[text()="2016-01-01"]'));
  }

  /**
   * Tests if Ajax callback works on datetime element.
   */
  public function testDateTimeAjaxCallback() {

    // Test Ajax callback when datetime changes.
    $this->drupalGet('ajax_forms_test_ajax_element_form');
    $this->assertNotEmpty($this->getSession()->getPage()->find('xpath', '//div[@id="ajax_datetime_value"][text()="No datetime selected."]'));
    $this->getSession()->executeScript('jQuery("[data-drupal-selector=edit-datetime-date]").val("2016-01-01");');
    $this->getSession()->executeScript('jQuery("[data-drupal-selector=edit-datetime-time]").val("12:00:00").trigger("change");');
    $this->assertNotEmpty($this->assertSession()->waitForElement('xpath', '//div[@id="ajax_datetime_value"]/div[text()="2016-01-01 12:00:00"]'));
  }

}
