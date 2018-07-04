<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Performs tests on AJAX forms in cached pages.
 *
 * @group Ajax
 */
class AjaxFormPageCacheTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_test', 'ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();
  }

  /**
   * Return the build id of the current form.
   */
  protected function getFormBuildId() {
    $build_id_fields = $this->xpath('//input[@name="form_build_id"]');
    $this->assertEquals(count($build_id_fields), 1, 'One form build id field on the page');
    return $build_id_fields[0]->getValue();
  }

  /**
   * Create a simple form, then submit the form via AJAX to change to it.
   */
  public function testSimpleAJAXFormValue() {
    $this->drupalGet('ajax_forms_test_get_form');
    $this->assertEquals($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $build_id_initial = $this->getFormBuildId();

    // Changing the value of a select input element, triggers a AJAX
    // request/response. The callback on the form responds with three AJAX
    // commands:
    // - UpdateBuildIdCommand
    // - HtmlCommand
    // - DataCommand
    $session = $this->getSession();
    $session->getPage()->selectFieldOption('select', 'green');

    // Wait for the DOM to update. The HtmlCommand will update
    // #ajax_selected_color to reflect the color change.
    $green_div = $this->assertSession()->waitForElement('css', "#ajax_selected_color div:contains('green')");
    $this->assertNotNull($green_div, 'DOM update: The selected color DIV is green.');

    // Confirm the operation of the UpdateBuildIdCommand.
    $build_id_first_ajax = $this->getFormBuildId();
    $this->assertNotEquals($build_id_initial, $build_id_first_ajax, 'Build id is changed in the form_build_id element on first AJAX submission');

    // Changing the value of a select input element, triggers a AJAX
    // request/response.
    $session->getPage()->selectFieldOption('select', 'red');

    // Wait for the DOM to update.
    $red_div = $this->assertSession()->waitForElement('css', "#ajax_selected_color div:contains('red')");
    $this->assertNotNull($red_div, 'DOM update: The selected color DIV is red.');

    // Confirm the operation of the UpdateBuildIdCommand.
    $build_id_second_ajax = $this->getFormBuildId();
    $this->assertNotEquals($build_id_first_ajax, $build_id_second_ajax, 'Build id changes on subsequent AJAX submissions');

    // Emulate a push of the reload button and then repeat the test sequence
    // this time with a page loaded from the cache.
    $session->reload();
    $this->assertEquals($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $build_id_from_cache_initial = $this->getFormBuildId();
    $this->assertEquals($build_id_initial, $build_id_from_cache_initial, 'Build id is the same as on the first request');

    // Changing the value of a select input element, triggers a AJAX
    // request/response.
    $session->getPage()->selectFieldOption('select', 'green');

    // Wait for the DOM to update.
    $green_div2 = $this->assertSession()->waitForElement('css', "#ajax_selected_color div:contains('green')");
    $this->assertNotNull($green_div2, 'DOM update: After reload - the selected color DIV is green.');

    $build_id_from_cache_first_ajax = $this->getFormBuildId();
    $this->assertNotEquals($build_id_from_cache_initial, $build_id_from_cache_first_ajax, 'Build id is changed in the simpletest-DOM on first AJAX submission');
    $this->assertNotEquals($build_id_first_ajax, $build_id_from_cache_first_ajax, 'Build id from first user is not reused');

    // Changing the value of a select input element, triggers a AJAX
    // request/response.
    $session->getPage()->selectFieldOption('select', 'red');

    // Wait for the DOM to update.
    $red_div2 = $this->assertSession()->waitForElement('css', "#ajax_selected_color div:contains('red')");
    $this->assertNotNull($red_div2, 'DOM update: After reload - the selected color DIV is red.');

    $build_id_from_cache_second_ajax = $this->getFormBuildId();
    $this->assertNotEquals($build_id_from_cache_first_ajax, $build_id_from_cache_second_ajax, 'Build id changes on subsequent AJAX submissions');

  }

  /**
   * Tests that updating the text field trigger an AJAX request/response.
   *
   * @see \Drupal\system\Tests\Ajax\ElementValidationTest::testAjaxElementValidation()
   */
  public function testAjaxElementValidation() {
    $this->drupalGet('ajax_validation_test');
    // Changing the value of the textfield will trigger an AJAX
    // request/response.
    $this->getSession()->getPage()->fillField('drivertext', 'some dumb text');

    // When the AJAX command updates the DOM a <ul> unsorted list
    // "message__list" structure will appear on the page echoing back the
    // "some dumb text" message.
    $placeholder = $this->assertSession()->waitForElement('css', "ul.messages__list li.messages__item em:contains('some dumb text')");
    $this->assertNotNull($placeholder, 'Message structure containing input data located.');
  }

}
