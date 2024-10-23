<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Tests adding messages via AJAX command.
 *
 * @group Ajax
 */
class MessageCommandTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests AJAX MessageCommand use in a form.
   */
  public function testMessageCommand(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('ajax-test/message');
    $page->pressButton('Make Message In Default Location');
    $this->waitForMessageVisible('I am a message in the default location.');
    $this->assertAnnounceContains('I am a message in the default location.');
    $assert_session->elementsCount('css', '.messages__wrapper .messages', 1);

    $page->pressButton('Make Message In Alternate Location');
    $this->waitForMessageVisible('I am a message in an alternate location.', '#alternate-message-container');
    $assert_session->pageTextContains('I am a message in the default location.');
    $this->assertAnnounceContains('I am a message in an alternate location.');
    $assert_session->elementsCount('css', '.messages__wrapper .messages', 1);
    $assert_session->elementsCount('css', '#alternate-message-container .messages', 1);

    $page->pressButton('Make Warning Message');
    $this->waitForMessageVisible('I am a warning message in the default location.', NULL, 'warning');
    $assert_session->pageTextNotContains('I am a message in the default location.');
    $assert_session->elementsCount('css', '.messages__wrapper .messages', 1);
    $assert_session->elementsCount('css', '#alternate-message-container .messages', 1);

    $this->drupalGet('ajax-test/message');
    // Test that by default, previous messages in a location are removed.
    for ($i = 0; $i < 6; $i++) {
      $page->pressButton('Make Message In Default Location');
      $this->waitForMessageVisible('I am a message in the default location.');
      $assert_session->elementsCount('css', '.messages__wrapper .messages', 1);

      $page->pressButton('Make Warning Message');
      $this->waitForMessageVisible('I am a warning message in the default location.', NULL, 'warning');
      // Test that setting MessageCommand::$option['announce'] => '' suppresses
      // screen reader announcement.
      $this->assertAnnounceNotContains('I am a warning message in the default location.');
      $this->waitForMessageRemoved('I am a message in the default location.');
      $assert_session->elementsCount('css', '.messages__wrapper .messages', 1);
    }

    // Test that if MessageCommand::clearPrevious is FALSE, messages will not
    // be cleared.
    $this->drupalGet('ajax-test/message');
    for ($i = 1; $i < 7; $i++) {
      $page->pressButton('Make Message In Alternate Location');
      $expected_count = $page->waitFor(10, function () use ($i, $page) {
        return count($page->findAll('css', '#alternate-message-container .messages')) === $i;
      });
      $this->assertTrue($expected_count);
      $this->assertAnnounceContains('I am a message in an alternate location.');
    }
  }

  /**
   * Tests methods in JsWebAssert related to status messages.
   */
  public function testJsStatusMessageAssertions(): void {
    $page = $this->getSession()->getPage();

    $this->drupalGet('ajax-test/message');

    $page->pressButton('Make Message In Default Location');
    $this->assertSession()->statusMessageContainsAfterWait('I am a message in the default location.');

    $page->pressButton('Make Message In Alternate Location');
    $this->assertSession()->statusMessageContainsAfterWait('I am a message in an alternate location.', 'status');

    $page->pressButton('Make Warning Message');
    $this->assertSession()->statusMessageContainsAfterWait('I am a warning message in the default location.', 'warning');

    // Reload and test some negative assertions.
    $this->drupalGet('ajax-test/message');

    $page->pressButton('Make Message In Default Location');
    // Use message that is not on page.
    $this->assertSession()->statusMessageNotContainsAfterWait('This is not a real message');

    $page->pressButton('Make Message In Alternate Location');
    // Use message that exists but has the wrong type.
    $this->assertSession()->statusMessageNotContainsAfterWait('I am a message in an alternate location.', 'warning');

    // Test partial match.
    $page->pressButton('Make Warning Message');
    $this->assertSession()->statusMessageContainsAfterWait('I am a warning');

    // One more reload to try with different arg combinations.
    $this->drupalGet('ajax-test/message');

    $page->pressButton('Make Message In Default Location');
    $this->assertSession()->statusMessageExistsAfterWait();

    $page->pressButton('Make Message In Alternate Location');
    $this->assertSession()->statusMessageNotExistsAfterWait('error');

    $page->pressButton('Make Warning Message');
    $this->assertSession()->statusMessageExistsAfterWait('warning');

    // Perform a few assertions that should fail. We can only call
    // TestCase::expectException() once per test, so we make a few
    // try/catch blocks. We pass a relatively short timeout because
    // it is a waste of time to wait 10 seconds in these assertions
    // that we fully expect to fail.
    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageContainsAfterWait('Not a real message', NULL, 1000);
    }
    catch (ExpectationFailedException) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'JsWebAssert::statusMessageContainsAfterWait() did not fail when it should have failed.');

    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageNotContainsAfterWait('I am a warning', NULL, 1000);
    }
    catch (ExpectationFailedException) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'JsWebAssert::statusMessageNotContainsAfterWait() did not fail when it should have failed.');

    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageExistsAfterWait('error', 1000);
    }
    catch (ExpectationFailedException) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'JsWebAssert::statusMessageExistsAfterWait() did not fail when it should have failed.');

    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageNotExistsAfterWait('warning', 1000);
    }
    catch (ExpectationFailedException) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'JsWebAssert::statusMessageNotExistsAfterWait() did not fail when it should have failed.');

    // Tests passing a bad status type.
    $this->expectException(\InvalidArgumentException::class);
    $this->assertSession()->statusMessageExistsAfterWait('not a valid type');
  }

  /**
   * Asserts that a message of the expected type appears.
   *
   * @param string $message
   *   The expected message.
   * @param string $selector
   *   The selector for the element in which to check for the expected message.
   * @param string $type
   *   The expected type.
   */
  protected function waitForMessageVisible($message, $selector = '[data-drupal-messages]', $type = 'status'): void {
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', $selector . ' .messages--' . $type . ':contains("' . $message . '")'));
  }

  /**
   * Asserts that a message of the expected type is removed.
   *
   * @param string $message
   *   The expected message.
   * @param string $selector
   *   The selector for the element in which to check for the expected message.
   * @param string $type
   *   The expected type.
   */
  protected function waitForMessageRemoved($message, $selector = '[data-drupal-messages]', $type = 'status'): void {
    $this->assertNotEmpty($this->assertSession()->waitForElementRemoved('css', $selector . ' .messages--' . $type . ':contains("' . $message . '")'));
  }

  /**
   * Checks for inclusion of text in #drupal-live-announce.
   *
   * @param string $expected_message
   *   The text expected to be present in #drupal-live-announce.
   *
   * @internal
   */
  protected function assertAnnounceContains(string $expected_message): void {
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElement('css', "#drupal-live-announce:contains('$expected_message')"));
  }

  /**
   * Checks for absence of the given text from #drupal-live-announce.
   *
   * @param string $expected_message
   *   The text expected to be absent from #drupal-live-announce.
   *
   * @internal
   */
  protected function assertAnnounceNotContains(string $expected_message): void {
    $assert_session = $this->assertSession();
    $this->assertEmpty($assert_session->waitForElement('css', "#drupal-live-announce:contains('$expected_message')", 1000));
  }

}
