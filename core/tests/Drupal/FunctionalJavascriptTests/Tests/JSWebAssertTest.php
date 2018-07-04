<?php

namespace Drupal\FunctionalJavascriptTests\Tests;

use Behat\Mink\Element\NodeElement;
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
  public static $modules = ['js_webassert_test'];

  /**
   * Tests that JSWebAssert assertions work correctly.
   */
  public function testJsWebAssert() {
    $this->drupalGet('js_webassert_test_form');

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

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
    $this->assertTrue($result instanceof NodeElement);

    $result = $page->findLink('Added link');
    $this->assertEmpty($result);
    $test_link->click();
    $result = $assert_session->waitForLink('Added link');
    $this->assertNotEmpty($result);
    $this->assertTrue($result instanceof NodeElement);

    $result = $page->findField('added_field');
    $this->assertEmpty($result);
    $test_field->click();
    $result = $assert_session->waitForField('added_field');
    $this->assertNotEmpty($result);
    $this->assertTrue($result instanceof NodeElement);

    $result = $page->findById('js_webassert_test_field_id');
    $this->assertEmpty($result);
    $test_id->click();
    $result = $assert_session->waitForId('js_webassert_test_field_id');
    $this->assertNotEmpty($result);
    $this->assertTrue($result instanceof NodeElement);

    // Test waitOnAjaxRequest. Verify the element is available after the wait
    // and the behaviors have run on completing by checking the value.
    $result = $page->findField('test_assert_wait_on_ajax_input');
    $this->assertEmpty($result);
    $test_wait_on_ajax->click();
    $assert_session->assertWaitOnAjaxRequest();
    $result = $page->findField('test_assert_wait_on_ajax_input');
    $this->assertNotEmpty($result);
    $this->assertTrue($result instanceof NodeElement);
    $this->assertEquals('js_webassert_test', $result->getValue());

    $result = $page->findButton('Added WaitForElementVisible');
    $this->assertEmpty($result);
    $test_wait_on_element_visible->click();
    $result = $assert_session->waitForElementVisible('named', ['button', 'Added WaitForElementVisible']);
    $this->assertNotEmpty($result);
    $this->assertTrue($result instanceof NodeElement);
    $this->assertEquals(TRUE, $result->isVisible());
  }

}
