<?php

namespace Drupal\Tests\contextual\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests edit mode.
 *
 * @group contextual
 */
class EditModeTest extends JavascriptTestBase {

  /**
   * CSS selector for Drupal's announce element.
   */
  const ANNOUNCE_SELECTOR = '#drupal-live-announce';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'block',
    'user',
    'system',
    'breakpoint',
    'toolbar',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->createUser([
      'administer blocks',
      'access contextual links',
      'access toolbar',
    ]));
    $this->placeBlock('system_powered_by_block', ['id' => 'powered']);
  }

  /**
   * Tests enabling and disabling edit mode.
   */
  public function testEditModeEnableDisalbe() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Get the page twice to ensure edit mode remains enabled after a new page
    // request.
    for ($page_get_count = 0; $page_get_count < 2; $page_get_count++) {
      $this->drupalGet('user');
      $expected_restricted_tab_count = 1 + count($page->findAll('css', '[data-contextual-id]'));

      // After the page loaded we need to additionally wait until the settings
      // tray Ajax activity is done.
      $web_assert->assertWaitOnAjaxRequest();

      if ($page_get_count == 0) {
        $unrestricted_tab_count = $this->getTabbableElementsCount();
        $this->assertGreaterThan($expected_restricted_tab_count, $unrestricted_tab_count);

        // Enable edit mode.
        // After the first page load the page will be in edit mode when loaded.
        $this->pressToolbarEditButton();
      }

      $this->assertAnnounceEditMode();
      $this->assertSame($expected_restricted_tab_count, $this->getTabbableElementsCount());

      // Disable edit mode.
      $this->pressToolbarEditButton();
      $this->assertAnnounceLeaveEditMode();
      $this->assertSame($unrestricted_tab_count, $this->getTabbableElementsCount());
      // Enable edit mode again.
      $this->pressToolbarEditButton();
      // Finally assert that the 'edit mode enabled' announcement is still
      // correct after toggling the edit mode at least once.
      $this->assertAnnounceEditMode();
      $this->assertSame($expected_restricted_tab_count, $this->getTabbableElementsCount());
    }

  }

  /**
   * Presses the toolbar edit mode.
   */
  protected function pressToolbarEditButton() {
    $edit_button = $this->getSession()->getPage()->find('css', '#toolbar-bar div.contextual-toolbar-tab button');
    $edit_button->press();
  }

  /**
   * Asserts that the correct message was announced when entering edit mode.
   */
  protected function assertAnnounceEditMode() {
    $web_assert = $this->assertSession();
    // Wait for contextual trigger button.
    $web_assert->waitForElementVisible('css', '.contextual trigger');
    $web_assert->elementContains('css', static::ANNOUNCE_SELECTOR, 'Tabbing is constrained to a set of');
    $web_assert->elementNotContains('css', static::ANNOUNCE_SELECTOR, 'Tabbing is no longer constrained by the Contextual module.');
  }

  /**
   * Assert that the correct message was announced when leaving edit mode.
   */
  protected function assertAnnounceLeaveEditMode() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Wait till all the contextual links are hidden.
    $page->waitFor(1, function () use ($page, $web_assert) {
      return empty($page->find('css', '.contextual .trigger.visually-hidden'));
    });
    $web_assert->elementContains('css', static::ANNOUNCE_SELECTOR, 'Tabbing is no longer constrained by the Contextual module.');
    $web_assert->elementNotContains('css', static::ANNOUNCE_SELECTOR, 'Tabbing is constrained to a set of');
  }

  /**
   * Gets the number of elements that are tabbable.
   *
   * @return int
   *   The number of tabbable elements.
   */
  protected function getTabbableElementsCount() {
    // Mark all tabbable elements.
    $this->getSession()->executeScript("jQuery(':tabbable').attr('data-marked', '');");
    // Count all marked elements.
    $count = count($this->getSession()->getPage()->findAll('css', "[data-marked]"));
    // Remove set attributes.
    $this->getSession()->executeScript("jQuery('[data-marked]').removeAttr('data-marked');");
    return $count;
  }

}
