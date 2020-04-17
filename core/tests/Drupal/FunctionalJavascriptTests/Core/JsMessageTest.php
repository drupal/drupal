<?php

namespace Drupal\FunctionalJavascriptTests\Core;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\js_message_test\Controller\JSMessageTestController;

/**
 * Tests core/drupal.message library.
 *
 * @group Javascript
 */
class JsMessageTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['js_message_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable the theme.
    \Drupal::service('theme_installer')->install(['test_messages']);
    $theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $theme_config->set('default', 'test_messages');
    $theme_config->save();
  }

  /**
   * Test click on links to show messages and remove messages.
   */
  public function testAddRemoveMessages() {
    $web_assert = $this->assertSession();
    $this->drupalGet('js_message_test_link');

    $current_messages = [];
    foreach (JSMessageTestController::getMessagesSelectors() as $messagesSelector) {
      $web_assert->elementExists('css', $messagesSelector);
      foreach (JSMessageTestController::getTypes() as $type) {
        $this->click('[id="add-' . $messagesSelector . '-' . $type . '"]');
        $selector = "$messagesSelector .messages.messages--$type";
        $msg_element = $web_assert->waitForElementVisible('css', $selector);
        $this->assertNotEmpty($msg_element, "Message element visible: $selector");
        $web_assert->elementContains('css', $selector, "This is a message of the type, $type. You be the judge of its importance.");
        $current_messages[$selector] = "This is a message of the type, $type. You be the judge of its importance.";
        $this->assertCurrentMessages($current_messages, $messagesSelector);
      }
      // Remove messages 1 by 1 and confirm the messages are expected.
      foreach (JSMessageTestController::getTypes() as $type) {
        $this->click('[id="remove-' . $messagesSelector . '-' . $type . '"]');
        $selector = "$messagesSelector .messages.messages--$type";
        // The message for this selector should not be on the page.
        unset($current_messages[$selector]);
        $this->assertCurrentMessages($current_messages, $messagesSelector);
      }
    }

    $messagesSelector = JSMessageTestController::getMessagesSelectors()[0];
    $current_messages = [];
    $types = JSMessageTestController::getTypes();
    $nb_messages = count($types) * 2;
    for ($i = 0; $i < $nb_messages; $i++) {
      $current_messages[] = "This is message number $i of the type, {$types[$i % count($types)]}. You be the judge of its importance.";
    }
    // Test adding multiple messages at once.
    // @see processMessages()
    $this->click('[id="add-multiple"]');
    $this->assertCurrentMessages($current_messages, $messagesSelector);
    $this->click('[id="remove-multiple"]');
    $this->assertCurrentMessages([], $messagesSelector);

    $current_messages = [];
    for ($i = 0; $i < $nb_messages; $i++) {
      $current_messages[] = "Msg-$i";
    }
    // The last message is of a different type and shouldn't get cleared.
    $last_message = 'Msg-' . count($current_messages);
    $current_messages[] = $last_message;
    $this->click('[id="add-multiple-error"]');
    $this->assertCurrentMessages($current_messages, $messagesSelector);
    $this->click('[id="remove-type"]');
    $this->assertCurrentMessages([$last_message], $messagesSelector);
    $this->click('[id="clear-all"]');
    $this->assertCurrentMessages([], $messagesSelector);

    // Confirm that when adding a message with an "id" specified but no status
    // that it receives the default status.
    $this->click('[id="id-no-status"]');
    $no_status_msg = 'Msg-id-no-status';
    $this->assertCurrentMessages([$no_status_msg], $messagesSelector);
    $web_assert->elementTextContains('css', "$messagesSelector .messages--status[data-drupal-message-id=\"my-special-id\"]", $no_status_msg);

  }

  /**
   * Asserts that currently shown messages match expected messages.
   *
   * @param array $expected_messages
   *   Expected messages.
   * @param string $messagesSelector
   *   The css selector for the containing messages element.
   */
  protected function assertCurrentMessages(array $expected_messages, $messagesSelector) {
    $expected_messages = array_values($expected_messages);
    $current_messages = [];
    if ($message_divs = $this->getSession()->getPage()->findAll('css', "$messagesSelector .messages")) {
      foreach ($message_divs as $message_div) {
        /** @var \Behat\Mink\Element\NodeElement $message_div */
        $current_messages[] = $message_div->getText();
      }
    }
    $this->assertEquals($expected_messages, $current_messages);
  }

}
