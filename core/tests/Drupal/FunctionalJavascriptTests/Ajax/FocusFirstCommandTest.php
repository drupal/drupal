<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests setting focus via AJAX command.
 *
 * @group Ajax
 */
class FocusFirstCommandTest extends WebDriverTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests AjaxFocusFirstCommand on a page.
   */
  public function testFocusFirst() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('ajax-test/focus-first');
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertNotContains($has_focus_id, ['edit-first-input', 'edit-first-container-input']);

    // Confirm that focus does not change if the selector targets a
    // non-focusable container containing no tabbable elements.
    $page->pressButton('SelectorNothingTabbable');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#edit-selector-has-nothing-tabbable[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-selector-has-nothing-tabbable', $has_focus_id);

    // Confirm that focus does not change if the page has no match for the
    // provided selector.
    $page->pressButton('SelectorNotExist');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#edit-selector-does-not-exist[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-selector-does-not-exist', $has_focus_id);

    // Confirm focus is moved to first tabbable element in a container.
    $page->pressButton('focusFirstContainer');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#edit-first-container-input[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-first-container-input', $has_focus_id);

    // Confirm focus is moved to first tabbable element in a form.
    $page->pressButton('focusFirstForm');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#ajax-test-focus-first-command-form #edit-first-input[data-has-focus]'));

    // Confirm the form has more than one input to confirm that focus is moved
    // to the first tabbable element in the container.
    $this->assertNotNull($page->find('css', '#ajax-test-focus-first-command-form #edit-second-input'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-first-input', $has_focus_id);

    // Confirm that the selector provided will use the first match in the DOM as
    // the container.
    $page->pressButton('SelectorMultipleMatches');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#edit-inside-same-selector-container-1[data-has-focus]'));
    $this->assertNotNull($page->findById('edit-inside-same-selector-container-2'));
    $this->assertNull($assert_session->waitForElementVisible('css', '#edit-inside-same-selector-container-2[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-inside-same-selector-container-1', $has_focus_id);

    // Confirm that if a container has no tabbable children, but is itself
    // focusable, then that container receives focus.
    $page->pressButton('focusableContainerNotTabbableChildren');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#focusable-container-without-tabbable-children[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('focusable-container-without-tabbable-children', $has_focus_id);
  }

}
