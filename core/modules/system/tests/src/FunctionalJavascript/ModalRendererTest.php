<?php

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that dialog links use different renderer services.
 *
 * @group system
 */
class ModalRendererTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'dialog_renderer_test'];

  /**
   * Tests that links respect 'data-dialog-renderer' attribute.
   */
  public function testModalRenderer() {
    $session_assert = $this->assertSession();
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Normal Modal!');
    // Neither of the wide modals should have been used.
    $style = $session_assert->waitForElementVisible('css', '.ui-dialog')->getAttribute('style');
    $this->assertNotContains('700px', $style);
    $this->assertNotContains('1000px', $style);
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Wide Modal!');
    $this->assertNotEmpty($session_assert->waitForElementVisible('css', '.ui-dialog[style*="width: 700px;"]'));
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Extra Wide Modal!');
    $this->assertNotEmpty($session_assert->waitForElementVisible('css', '.ui-dialog[style*="width: 1000px;"]'));
  }

}
