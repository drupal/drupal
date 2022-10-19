<?php

namespace Drupal\FunctionalJavascriptTests\Tests;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementHtmlException;
use Drupal\Component\Utility\Timer;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests for the JSWebAssert class.
 *
 * @group javascript
 */
class JSWebAssertTest extends WebDriverTestBase {

  /**
   * Required modules.
   *
   * @var array
   */
  protected static $modules = ['js_webassert_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that JSWebAssert assertions work correctly.
   */
  public function testJsWebAssert() {
    $this->drupalGet('js_webassert_test_form');

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    $assert_session->elementExists('css', '[data-drupal-selector="edit-test-assert-no-element-after-wait-pass"]');
    $page->findButton('Test assertNoElementAfterWait: pass')->press();
    $assert_session->assertNoElementAfterWait('css', '[data-drupal-selector="edit-test-assert-no-element-after-wait-pass"]', 1000);

    $assert_session->elementExists('css', '[data-drupal-selector="edit-test-assert-no-element-after-wait-fail"]');
    $page->findButton('Test assertNoElementAfterWait: fail')->press();
    try {
      Timer::start('JSWebAssertTest');
      $assert_session->assertNoElementAfterWait('css', '[data-drupal-selector="edit-test-assert-no-element-after-wait-fail"]', 500, 'Element exists on page after too short wait.');
      // This test is fragile if webdriver responses are very slow for some
      // reason. If they are, do not fail the test.
      // @todo https://www.drupal.org/project/drupal/issues/3316317 remove this
      //   workaround.
      if (Timer::read('JSWebAssertTest') < 1000) {
        $this->fail("Element not exists on page after too short wait.");
      }
    }
    catch (ElementHtmlException $e) {
      $this->assertSame('Element exists on page after too short wait.', $e->getMessage());
    }

    $assert_session->assertNoElementAfterWait('css', '[data-drupal-selector="edit-test-assert-no-element-after-wait-fail"]', 2500, 'Element remove after another wait.ss');

    $test_button = $page->findButton('Add button');
    $test_link = $page->findButton('Add link');
    $test_field = $page->findButton('Add field');
    $test_id = $page->findButton('Add ID');
    $test_wait_on_ajax = $page->findButton('Test assertWaitOnAjaxRequest');
    $test_wait_on_element_visible = $page->findButton('Test waitForElementVisible');

    // Test the wait...() methods by first checking the fields aren't available
    // and then are available after the wait method.
    $result = $page->findButton('Added button');
    $this->assertEmpty($result);
    $test_button->click();
    $result = $assert_session->waitForButton('Added button');
    $this->assertNotEmpty($result);
    $this->assertInstanceOf(NodeElement::class, $result);

    $result = $page->findLink('Added link');
    $this->assertEmpty($result);
    $test_link->click();
    $result = $assert_session->waitForLink('Added link');
    $this->assertNotEmpty($result);
    $this->assertInstanceOf(NodeElement::class, $result);

    $result = $page->findField('added_field');
    $this->assertEmpty($result);
    $test_field->click();
    $result = $assert_session->waitForField('added_field');
    $this->assertNotEmpty($result);
    $this->assertInstanceOf(NodeElement::class, $result);

    $result = $page->findById('js_webassert_test_field_id');
    $this->assertEmpty($result);
    $test_id->click();
    $result = $assert_session->waitForId('js_webassert_test_field_id');
    $this->assertNotEmpty($result);
    $this->assertInstanceOf(NodeElement::class, $result);

    // Test waitOnAjaxRequest. Verify the element is available after the wait
    // and the behaviors have run on completing by checking the value.
    $result = $page->findField('test_assert_wait_on_ajax_input');
    $this->assertEmpty($result);
    $test_wait_on_ajax->click();
    $assert_session->assertWaitOnAjaxRequest();
    $result = $page->findField('test_assert_wait_on_ajax_input');
    $this->assertNotEmpty($result);
    $this->assertInstanceOf(NodeElement::class, $result);
    $this->assertEquals('js_webassert_test', $result->getValue());

    $result = $page->findButton('Added WaitForElementVisible');
    $this->assertEmpty($result);
    $test_wait_on_element_visible->click();
    $result = $assert_session->waitForElementVisible('named', ['button', 'Added WaitForElementVisible']);
    $this->assertNotEmpty($result);
    $this->assertInstanceOf(NodeElement::class, $result);
    $this->assertEquals(TRUE, $result->isVisible());

    $this->drupalGet('js_webassert_test_page');
    $result = $assert_session->waitForElementVisible('named', ['id', 'test_text']);
    $this->assertSame('test_text', $result->getAttribute('id'));
    // Ensure that the javascript has replaced the element 1100 times.
    $assert_session->pageTextContains('New Text!! 1100');
  }

}
