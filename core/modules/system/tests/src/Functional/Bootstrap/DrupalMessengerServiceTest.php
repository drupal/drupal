<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Bootstrap;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Tests the Messenger service.
 *
 * @group Bootstrap
 */
class DrupalMessengerServiceTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Messenger service.
   */
  public function testDrupalMessengerService(): void {
    // The page at system_test.messenger_service route sets two messages and
    // then removes the first before it is displayed.
    $this->drupalGet(Url::fromRoute('system_test.messenger_service'));
    $this->assertSession()->pageTextNotContains('First message (removed).');
    $this->assertSession()->responseContains('Second message with <em>markup!</em> (not removed).');

    // Ensure duplicate messages are handled as expected.
    $this->assertSession()->pageTextMatchesCount(1, '/Non Duplicated message/');
    $this->assertSession()->pageTextMatchesCount(3, '/Duplicated message/');

    // Ensure Markup objects are rendered as expected.
    $this->assertSession()->responseContains('Markup with <em>markup!</em>');
    $this->assertSession()->pageTextMatchesCount(1, '/Markup with markup!/');
    $this->assertSession()->responseContains('Markup2 with <em>markup!</em>');

    // Ensure when the same message is of different types it is not duplicated.
    $this->assertSession()->pageTextMatchesCount(1, '$Non duplicate Markup / string.$');
    $this->assertSession()->pageTextMatchesCount(2, '$Duplicate Markup / string.$');

    // Ensure that strings that are not marked as safe are escaped.
    $this->assertSession()->assertEscaped('<em>This<span>markup will be</span> escaped</em>.');

    // Ensure messages survive a container rebuild.
    $assert = $this->assertSession();
    $this->drupalLogin($this->drupalCreateUser(['administer modules']));

    $edit = [];
    $edit["modules[help][enable]"] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $assert->pageTextContains('Help has been installed');
    $assert->pageTextContains('system_test_preinstall_module called');
  }

  /**
   * Tests assertion methods in WebAssert related to status messages.
   */
  public function testStatusMessageAssertions(): void {
    $this->drupalGet(Url::fromRoute('system_test.status_messages_for_assertions'));

    // Use the simple messages to test basic functionality.
    // Test WebAssert::statusMessagesExists().
    $this->assertSession()->statusMessageExists();
    $this->assertSession()->statusMessageExists('status');
    $this->assertSession()->statusMessageExists('error');
    $this->assertSession()->statusMessageExists('warning');

    // WebAssert::statusMessageContains().
    $this->assertSession()->statusMessageContains('My Status Message');
    $this->assertSession()->statusMessageContains('My Error Message');
    $this->assertSession()->statusMessageContains('My Warning Message');
    // Test partial match.
    $this->assertSession()->statusMessageContains('My Status');
    // Test with second arg.
    $this->assertSession()->statusMessageContains('My Status Message', 'status');
    $this->assertSession()->statusMessageContains('My Error Message', 'error');
    $this->assertSession()->statusMessageContains('My Warning Message', 'warning');

    // Test WebAssert::statusMessageNotContains().
    $this->assertSession()->statusMessageNotContains('My Status Message is fake');
    $this->assertSession()->statusMessageNotContains('My Status Message is fake', 'status');
    $this->assertSession()->statusMessageNotContains('My Error Message', 'status');
    $this->assertSession()->statusMessageNotContains('My Status Message', 'error');

    // Check that special characters get handled correctly.
    $this->assertSession()->statusMessageContains('This has " in the middle');
    $this->assertSession()->statusMessageContains('This has \' in the middle');
    $this->assertSession()->statusMessageContains('<em>This<span>markup will be</span> escaped</em>');
    $this->assertSession()->statusMessageContains('Peaches & cream');
    $this->assertSession()->statusMessageNotContains('Peaches &amp; cream');

    // Go to a new route that only has messages of type 'status'.
    $this->drupalGet(Url::fromRoute('system_test.messenger_service'));
    // Test WebAssert::statusMessageNotExists().
    $this->assertSession()->statusMessageNotExists('error');
    $this->assertSession()->statusMessageNotExists('warning');

    // Perform a few assertions that should fail. We can only call
    // TestCase::expectException() once per test, so we make a few
    // try/catch blocks.
    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageContains('This message is not real');
    }
    catch (AssertionFailedError $e) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'WebAssert::statusMessageContains() did not fail when it should have failed.');

    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageNotContains('markup');
    }
    catch (AssertionFailedError $e) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'WebAssert::statusMessageNotContains() did not fail when it should have failed.');

    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageExists('error');
    }
    catch (AssertionFailedError $e) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'WebAssert::statusMessageExists() did not fail when it should have failed.');

    $expected_failure_occurred = FALSE;
    try {
      $this->assertSession()->statusMessageNotExists();
    }
    catch (AssertionFailedError $e) {
      $expected_failure_occurred = TRUE;
    }
    $this->assertTrue($expected_failure_occurred, 'WebAssert::statusMessageNotExists() did not fail when it should have failed.');

    // Tests passing a bad status type.
    $this->expectException(\InvalidArgumentException::class);
    $this->assertSession()->statusMessageExists('not a valid type');
  }

}
