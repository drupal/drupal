<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\FunctionalJavascriptTests\Core\JsMessageTest;
use Drupal\js_message_test\Controller\JSMessageTestController;

/**
 * Runs OliveroMessagesTest in Olivero.
 *
 * @group olivero
 *
 * @see \Drupal\FunctionalJavascriptTests\Core\JsMessageTest.
 */
class OliveroMessagesTest extends JsMessageTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'js_message_test',
    'system',
    'block',
  ];

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
    \Drupal::service('theme_installer')->install(['olivero']);
    $theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $theme_config->set('default', 'olivero');
    $theme_config->save();
  }

  /**
   * Tests data-drupal-selector="messages" exists.
   */
  public function testDataDrupalSelectors(): void {
    $web_assert = $this->assertSession();
    $this->drupalGet('js_message_test_link');

    foreach (JSMessageTestController::getMessagesSelectors() as $messagesSelector) {
      $web_assert->elementExists('css', $messagesSelector);
      foreach (JSMessageTestController::getTypes() as $type) {
        $this->click('[id="add-' . $messagesSelector . '-' . $type . '"]');
        $selector = '[data-drupal-selector="messages"]';
        $msg_element = $web_assert->waitForElementVisible('css', $selector);
        $this->assertNotEmpty($msg_element, "Message element visible: $selector");
      }
    }
  }

}
