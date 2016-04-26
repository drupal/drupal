<?php

namespace Drupal\Tests\toolbar\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the JavaScript functionality of the toolbar.
 *
 * @group toolbar
 */
class ToolbarIntegrationTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['toolbar', 'node'];

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

    $this->drupalGet('<front>');
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

}
