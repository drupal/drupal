<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that dialog links use different renderer services.
 */
#[Group('system')]
#[RunTestsInSeparateProcesses]
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
  public function testModalRenderer(): void {
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
    $this->clickLink('Attack modal!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');
    $session_assert->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-buttonpane');

    // Tabbable should focus the first tabbable item inside button pane.
    $this->assertJsCondition('document.activeElement === tabbable.tabbable(document.querySelector(".ui-dialog .ui-dialog-buttonpane"))[0]');
    $this->assertSession()->elementNotExists('css', 'script[data-attack]');

    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Attack modal raw!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');
    $session_assert->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-buttonpane');

    // Tabbable should focus the first tabbable item inside button pane.
    $this->assertJsCondition('document.activeElement === tabbable.tabbable(document.querySelector(".ui-dialog .ui-dialog-buttonpane"))[0]');
    $this->assertSession()->elementNotExists('css', 'script[data-attack]');

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

    // By default, buttons within "action" form elements are changed to jQuery
    // ui buttons and moved into the 'ui-dialog-buttonpane' container.
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Auto buttons default!');
    $this->assertNotNull($session_assert->waitForElement('css', '.ui-dialog-buttonpane .ui-dialog-buttonset .js-form-submit'));
    $session_assert->elementExists('css', '.ui-dialog-buttonpane .ui-dialog-buttonset .js-form-submit');

    // When the drupalAutoButtons option is false, buttons SHOULD NOT be moved
    // into the 'ui-dialog-buttonpane' container.
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Auto buttons false!');
    $this->assertNotNull($session_assert->waitForElement('css', '.form-actions'));
    $session_assert->elementExists('css', '.form-actions');
    $session_assert->elementNotExists('css', '.ui-dialog-buttonpane');

    // When the drupalAutoButtons option is true, buttons SHOULD be moved
    // into the 'ui-dialog-buttonpane' container.
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Auto buttons true!');
    $this->assertNotNull($session_assert->waitForElement('css', '.ui-dialog-buttonpane .ui-dialog-buttonset .js-form-submit'));
    $session_assert->elementExists('css', '.ui-dialog-buttonpane .ui-dialog-buttonset .js-form-submit');

    // Test event handler attributes are filtered from attack modal buttons.
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Attack modal!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');
    $session_assert->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-buttonpane');

    // Verify buttons are present.
    $buttons = $this->getSession()->getPage()->findAll('css', '.ui-dialog-buttonpane button');
    $this->assertGreaterThan(0, count($buttons));

    // Get the HTML of the button pane to check for event handler attributes.
    $button_pane_html = $this->getSession()->getPage()->find('css', '.ui-dialog-buttonpane')->getHtml();

    // Verify that no event handler attributes are present in button HTML.
    $event_handlers = [
      'onclick', 'onmouseover', 'onscroll', 'onfocus', 'onblur',
      'onkeydown', 'onload', 'onmousedown', 'onmouseup', 'onkeyup',
    ];

    foreach ($event_handlers as $handler) {
      $session_assert->elementNotExists('css', "[$handler]");
    }

    // Verify that button text content is preserved while malicious attributes are filtered.
    $this->assertStringContainsString('Button with onscroll', $button_pane_html);
    $this->assertStringContainsString('Button with multiple handlers', $button_pane_html);

    // Test the raw attack modal version as well.
    $this->drupalGet('/dialog_renderer-test-links');
    $this->clickLink('Attack modal raw!');
    $session_assert->waitForElementVisible('css', '.ui-dialog');
    $session_assert->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-buttonpane');

    // Get the HTML again for the raw version.
    $button_pane_html_raw = $this->getSession()->getPage()->find('css', '.ui-dialog-buttonpane')->getHtml();

    // Verify event handlers are also not present in the raw version.
    foreach ($event_handlers as $handler) {
      $this->assertStringNotContainsString($handler . '=', $button_pane_html_raw);
    }

    // Verify that legitimate button content is preserved in the raw version.
    $this->assertStringContainsString('Raw combined attack', $button_pane_html_raw);
  }

  /**
   * Confirm focus management of a dialog openers in a dropbutton.
   */
  public function testOpenerInDropbutton(): void {
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
