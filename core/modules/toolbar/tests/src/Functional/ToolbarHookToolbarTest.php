<?php

namespace Drupal\Tests\toolbar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the implementation of hook_toolbar() by a module.
 *
 * @group toolbar
 */
class ToolbarHookToolbarTest extends BrowserTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['toolbar', 'toolbar_test', 'test_page_test'];

  protected function setUp() {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser(['access toolbar']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests for a tab and tray provided by a module implementing hook_toolbar().
   */
  public function testHookToolbar() {
    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    // Assert that the tab registered by toolbar_test is present.
    $this->assertRaw('id="toolbar-tab-testing"');

    // Assert that the tab item descriptions are present.
    $this->assertRaw('title="Test tab"');

    // Assert that the tray registered by toolbar_test is present.
    $this->assertRaw('id="toolbar-tray-testing"');

    // Assert that tray item descriptions are present.
    $this->assertRaw('title="Test link 1 title"');
  }

}
