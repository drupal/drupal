<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Performs tests on AJAX forms in cached pages.
 *
 * @group Ajax
 */
class AjaxFormPageCacheTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test', 'ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();
  }

  /**
   * Return the build id of the current form.
   */
  protected function getFormBuildId() {
    // Ensure the hidden 'form_build_id' field is unique.
    $this->assertSession()->elementsCount('xpath', '//input[@name="form_build_id"]', 1);
    return $this->assertSession()->hiddenFieldExists('form_build_id')->getValue();
  }

  /**
   * Create a simple form, then submit the form via AJAX to change to it.
   */
  public function testSimpleAJAXFormValue() {
    $this->drupalGet('ajax_forms_test_get_form');
    $build_id_initial = $this->getFormBuildId();

    // Changing the value of a select input element, triggers an AJAX
    // request/response. The callback on the form responds with three AJAX
    // commands:
    // - UpdateBuildIdCommand
    // - HtmlCommand
    // - DataCommand
    $session = $this->getSession();
    $session->getPage()->selectFieldOption('select', 'green');

    // Wait for the DOM to update. The HtmlCommand will update
    // #ajax_selected_color to reflect the color change.
    $green_span = $this->assertSession()->waitForElement('css', "#ajax_selected_color:contains('green')");
    $this->assertNotNull($green_span, 'DOM update: The selected color SPAN is green.');

    // Confirm the operation of the UpdateBuildIdCommand.
    $build_id_first_ajax = $this->getFormBuildId();
    $this->assertNotEquals($build_id_initial, $build_id_first_ajax, 'Build id is changed in the form_build_id element on first AJAX submission');

    // Changing the value of a select input element, triggers an AJAX
    // request/response.
    $session->getPage()->selectFieldOption('select', 'red');

    // Wait for the DOM to update.
    $red_span = $this->assertSession()->waitForElement('css', "#ajax_selected_color:contains('red')");
    $this->assertNotNull($red_span, 'DOM update: The selected color SPAN is red.');

    // Confirm the operation of the UpdateBuildIdCommand.
    $build_id_second_ajax = $this->getFormBuildId();
    $this->assertNotEquals($build_id_first_ajax, $build_id_second_ajax, 'Build id changes on subsequent AJAX submissions');

    // Emulate a push of the reload button and then repeat the test sequence
    // this time with a page loaded from the cache.
    $session->reload();
    $build_id_from_cache_initial = $this->getFormBuildId();
    $this->assertEquals($build_id_initial, $build_id_from_cache_initial, 'Build id is the same as on the first request');

    // Changing the value of a select input element, triggers an AJAX
    // request/response.
    $session->getPage()->selectFieldOption('select', 'green');

    // Wait for the DOM to update.
    $green_span2 = $this->assertSession()->waitForElement('css', "#ajax_selected_color:contains('green')");
    $this->assertNotNull($green_span2, 'DOM update: After reload - the selected color SPAN is green.');

    $build_id_from_cache_first_ajax = $this->getFormBuildId();
    $this->assertNotEquals($build_id_from_cache_initial, $build_id_from_cache_first_ajax, 'Build id is changed in the DOM on first AJAX submission');
    $this->assertNotEquals($build_id_first_ajax, $build_id_from_cache_first_ajax, 'Build id from first user is not reused');

    // Changing the value of a select input element, triggers an AJAX
    // request/response.
    $session->getPage()->selectFieldOption('select', 'red');

    // Wait for the DOM to update.
    $red_span2 = $this->assertSession()->waitForElement('css', "#ajax_selected_color:contains('red')");
    $this->assertNotNull($red_span2, 'DOM update: After reload - the selected color SPAN is red.');

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
    $field = $this->getSession()->getPage()->findField('drivertext');
    $field->setValue('some dumb text');
    $field->blur();

    // When the AJAX command updates the DOM a <ul> unsorted list
    // "message__list" structure will appear on the page echoing back the
    // "some dumb text" message.
    $placeholder = $this->assertSession()->waitForElement('css', "ul.messages__list li.messages__item em:contains('some dumb text')");
    $this->assertNotNull($placeholder, 'Message structure containing input data located.');
  }

}
