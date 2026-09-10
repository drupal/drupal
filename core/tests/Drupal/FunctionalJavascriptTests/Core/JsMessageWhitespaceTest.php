<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Core;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that message.js handles whitespace in empty message wrappers.
 */
#[Group('Javascript')]
#[RunTestsInSeparateProcesses]
class JsMessageWhitespaceTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['js_message_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that message.js handles whitespace in empty message wrappers.
   *
   * This test verifies that getting the message wrapper does not fail when
   * themes have a new line or something in their template.
   */
  public function testMessageClearWithWhitespace(): void {
    $this->drupalGet('js_message_test_link');
    $this->assertSession()->pageTextContains('JsMessageLinks');

    // Simulate real-world scenario: theme template outputs whitespace
    // in the messages wrapper but no actual message elements.
    $this->getSession()->executeScript("
      var wrapper = document.querySelector('[data-drupal-messages]');
      if (wrapper) {
        // Clear existing content
        wrapper.innerHTML = '';
        // Add whitespace like a theme template might (newlines, spaces, tabs)
        wrapper.innerHTML = '\\n  \\t\\n  \\n';
      }
    ");

    // Drupal.Message should work without errors when the wrapper contains only
    // whitespace.
    $this->getSession()->executeScript("
      try {
        var msg = new Drupal.Message();
        msg.add('Test message', {type: 'status'});
        msg.clear();
        // Mark success if no errors thrown
        document.body.setAttribute('data-message-test-success', 'true');
      } catch (e) {
        // Mark failure if any errors thrown
        document.body.setAttribute('data-message-test-error', e.toString());
      }
    ");

    // Verify the test completed successfully without JavaScript errors.
    $error = $this->getSession()->evaluateScript("
      return document.body.getAttribute('data-message-test-error');
    ");
    $this->assertNull($error);

    // Verify the wrapper still exists after the operations.
    $this->assertSession()->elementExists('css', '[data-drupal-messages]');
  }

}
