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

  /**
   * Confirm focus management of a dialog openers in a dropbutton.
   */
  public function testOpenerInDropbutton() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('dialog_renderer-collapsed-opener');

    // Open a modal using a link inside a dropbutton.
    $page->find('css', '.dropbutton-toggle button')->click();
    $modal_link = $assert_session->waitForElementVisible('css', '.secondary-action a');
    $modal_link->click();
    $assert_session->waitForElementVisible('css', '.ui-dialog');
    $assert_session->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-content');
    $page->pressButton('Close');

    // When the dialog "closes" it is still present, so wait on it switching to
    // `display: none;`.
    $assert_session->waitForElement('css', '.ui-dialog[style*="display: none;"]');

    // Confirm that when the modal closes, focus is moved to the first visible
    // and focusable item in the contextual link container, because the original
    // opener is not available.
    $this->assertJsCondition('document.activeElement === document.querySelector(".dropbutton-action a")');
  }

}
