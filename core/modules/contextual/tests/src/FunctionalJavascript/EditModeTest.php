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
   * Tests that Drupal.announce messages appear.
   */
  public function testAnnounceEditMode() {
    $web_assert = $this->assertSession();
    $this->drupalGet('user');

    // After the page loaded we need to additionally wait until the settings
    // tray Ajax activity is done.
    $web_assert->assertWaitOnAjaxRequest();

    // Enable edit mode.
    $this->pressToolbarEditButton();
    $this->assertAnnounceEditMode();
    // Disable edit mode.
    $this->pressToolbarEditButton();
    $this->assertAnnounceLeaveEditMode();
    // Enable edit mode again.
    $this->pressToolbarEditButton();
    // Finally assert that the 'edit mode enabled' announcement is still correct
    // after toggling the edit mode at least once.
    $this->assertAnnounceEditMode();
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

}
