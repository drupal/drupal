<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the toolbar.
 *
 * @group toolbar
 */
class ToolbarIntegrationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if the toolbar can be toggled with JavaScript.
   */
  public function testToolbarToggling() {
    $admin_user = $this->drupalCreateUser([
      'access toolbar',
      'administer site configuration',
      'access content overview',
    ]);
    $this->drupalLogin($admin_user);

    // Set size for horizontal toolbar.
    $this->getSession()->resizeWindow(1200, 600);
    $this->drupalGet('<front>');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'body.toolbar-horizontal'));
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.toolbar-tray'));

    $page = $this->getSession()->getPage();

    // Test that it is possible to toggle the toolbar tray.
    $content = $page->findLink('Content');
    $this->assertTrue($content->isVisible(), 'Toolbar tray is open by default.');
    $page->clickLink('Manage');
    $this->assertFalse($content->isVisible(), 'Toolbar tray is closed after clicking the "Manage" link.');
    $page->clickLink('Manage');
    $this->assertTrue($content->isVisible(), 'Toolbar tray is visible again after clicking the "Manage" button a second time.');

    // Test toggling the toolbar tray between horizontal and vertical.
    $tray = $page->findById('toolbar-item-administration-tray');
    $this->assertFalse($tray->hasClass('toolbar-tray-vertical'), 'Toolbar tray is not vertically oriented by default.');
    $page->pressButton('Vertical orientation');
    $this->assertTrue($tray->hasClass('toolbar-tray-vertical'), 'After toggling the orientation the toolbar tray is now displayed vertically.');

    $page->pressButton('Horizontal orientation');
    $this->assertTrue($tray->hasClass('toolbar-tray-horizontal'), 'After toggling the orientation a second time the toolbar tray is displayed horizontally again.');
  }

  /**
   * Tests that the orientation toggle is not shown for empty toolbar items.
   */
  public function testEmptyTray() {
    // Granting access to the toolbar but not any administrative menu links will
    // result in an empty toolbar tray for the "Manage" toolbar item.
    $admin_user = $this->drupalCreateUser([
      'access toolbar',
    ]);
    $this->drupalLogin($admin_user);

    // Set size for horizontal toolbar.
    $this->getSession()->resizeWindow(1200, 600);
    $this->drupalGet('<front>');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'body.toolbar-horizontal'));
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.toolbar-tray'));

    // Test that the orientation toggle does not appear.
    $page = $this->getSession()->getPage();
    $tray = $page->findById('toolbar-item-administration-tray');
    $this->assertTrue($tray->hasClass('toolbar-tray-horizontal'), 'Toolbar tray is horizontally oriented by default.');
    $this->assertSession()->elementNotExists('css', '#toolbar-item-administration-tray .toolbar-menu');
    $this->assertSession()->elementNotExists('css', '#toolbar-item-administration-tray .toolbar-toggle-orientation');
    $button = $page->findButton('Vertical orientation');
    $this->assertFalse($button->isVisible(), 'Orientation toggle from other tray is not visible');
  }

}
