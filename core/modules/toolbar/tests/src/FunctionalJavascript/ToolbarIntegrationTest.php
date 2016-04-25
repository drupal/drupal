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
      'access content overview'
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('<front>');

    // Test that it is possible to toggle the toolbar tray.
    $this->assertElementVisible('#toolbar-link-system-admin_content', 'Toolbar tray is open by default.');
    $this->click('#toolbar-item-administration');
    $this->assertElementNotVisible('#toolbar-link-system-admin_content', 'Toolbar tray is closed after clicking the "Manage" button.');
    $this->click('#toolbar-item-administration');
    $this->assertElementVisible('#toolbar-link-system-admin_content', 'Toolbar tray is visible again after clicking the "Manage" button a second time.');

    // Test toggling the toolbar tray between horizontal and vertical.
    $this->assertElementVisible('#toolbar-item-administration-tray.toolbar-tray-horizontal', 'Toolbar tray is horizontally oriented by default.');
    $this->assertElementNotPresent('#toolbar-item-administration-tray.toolbar-tray-vertical', 'Toolbar tray is not vertically oriented by default.');

    $this->click('#toolbar-item-administration-tray button.toolbar-icon-toggle-vertical');
    $this->assertJsCondition('jQuery("#toolbar-item-administration-tray").hasClass("toolbar-tray-vertical")');
    $this->assertElementVisible('#toolbar-item-administration-tray.toolbar-tray-vertical', 'After toggling the orientation the toolbar tray is now displayed vertically.');

    $this->click('#toolbar-item-administration-tray button.toolbar-icon-toggle-horizontal');
    $this->assertJsCondition('jQuery("#toolbar-item-administration-tray").hasClass("toolbar-tray-horizontal")');
    $this->assertElementVisible('#toolbar-item-administration-tray.toolbar-tray-horizontal', 'After toggling the orientation a second time the toolbar tray is displayed horizontally again.');
  }

}
