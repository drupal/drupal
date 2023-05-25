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
  protected static $modules = ['system', 'dialog_renderer_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that links respect 'data-dialog-renderer' attribute.
   */
  public function testModalRenderer() {
    $session_assert = $this->assertSession();
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Normal Modal!');

    // Neither of the wide modals should have been used.
    $style = $session_assert->waitForElementVisible('css', '.ui-dialog')->getAttribute('style');
    $this->assertStringNotContainsString('700px', $style);
    $this->assertStringNotContainsString('1000px', $style);

    // Tabbable should focus the close button when it is the only tabbable item.
    $this->assertJsCondition('document.activeElement === document.querySelector(".ui-dialog .ui-dialog-titlebar-close")');
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Wide Modal!');
    $this->assertNotEmpty($session_assert->waitForElementVisible('css', '.ui-dialog[style*="width: 700px;"]'));
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Extra Wide Modal!');
    $this->assertNotEmpty($session_assert->waitForElementVisible('css', '.ui-dialog[style*="width: 1000px;"]'));

    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Hidden close button modal!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');

    // Tabbable should focus the dialog itself when there is no other item.
    $this->assertJsCondition('document.activeElement === document.querySelector(".ui-dialog")');

    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Button pane modal!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');
    $session_assert->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-buttonpane');

    // Tabbable should focus the first tabbable item inside button pane.
    $this->assertJsCondition('document.activeElement === tabbable.tabbable(document.querySelector(".ui-dialog .ui-dialog-buttonpane"))[0]');

    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Content link modal!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');
    $session_assert->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-content');

    // Tabbable should focus the first tabbable item inside modal content.
    $this->assertJsCondition('document.activeElement === tabbable.tabbable(document.querySelector(".ui-dialog .ui-dialog-content"))[0]');

    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Auto focus modal!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');
    $session_assert->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-content');

    // Tabbable should focus the item with autofocus inside button pane.
    $this->assertJsCondition('document.activeElement === tabbable.tabbable(document.querySelector(".ui-dialog .ui-dialog-content"))[1]');
    $this->assertJsCondition('document.activeElement === document.querySelector(".ui-dialog .form-text")');
  }

}
