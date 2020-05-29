<?php

namespace Drupal\Tests\workspaces\FunctionalJavascript;

use Drupal\Tests\system\FunctionalJavascript\OffCanvasTestBase;

/**
 * Tests workspace settings stray integration.
 *
 * @group workspaces
 */
class WorkspaceToolbarIntegrationTest extends OffCanvasTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer workspaces',
      'access toolbar',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test workspace canvas can be toggled with JavaScript.
   */
  public function testWorkspaceCanvasToggling() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Set size for horizontal toolbar.
    $this->getSession()->resizeWindow(1200, 600);
    $this->drupalGet('<front>');
    // Wait for toolbar to appear.
    $this->assertNotEmpty($assert_session->waitForElement('css', 'body.toolbar-horizontal'));

    // Open workspace canvas.
    $page->clickLink('Switch workspace');
    $this->waitForOffCanvasToOpen('top');
    $assert_session->elementExists('css', '.workspaces-dialog');

    // Close Canvas.
    $page->pressButton('Close');
    $this->waitForOffCanvasToClose();
    $assert_session->assertNoElementAfterWait('css', '.workspaces-dialog');
  }

  /**
   * Test workspace switch and landing page behavior.
   */
  public function testWorkspaceSwitch() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Wait for toolbar to appear.
    $this->getSession()->resizeWindow(1200, 600);
    $this->drupalGet('admin');

    // Wait for toolbar to appear.
    $this->assertNotEmpty($assert_session->waitForElement('css', 'body.toolbar-horizontal'));

    // Open workspace canvas.
    $page->clickLink('Switch workspace');
    $this->waitForOffCanvasToOpen('top');

    // Click 'stage' workspace and confirm switch.
    $page->clickLink('Stage');
    $this->assertElementVisibleAfterWait('css', '.workspace-activate-form.workspace-confirm-form');
    $page->find('css', '.ui-dialog-buttonset .button--primary')->click();
    $assert_session->waitForElementVisible('css', '.messages--status');

    // Make sure we stay on same page after switch.
    $assert_session->responseContains('<em class="placeholder">Stage</em> is now the active workspace.');
    $assert_session->addressEquals('admin');
  }

}
