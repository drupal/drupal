<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests workspace switching functionality.
 *
 * @group workspace
 */
class WorkspaceSwitcherTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'workspace'];

  /**
   * Test switching workspace via the switcher block and admin page.
   */
  public function testSwitchingWorkspaces() {
    $permissions = [
      'create workspace',
      'edit own workspace',
      'view own workspace',
      'bypass entity access own workspace',
    ];

    $this->setupWorkspaceSwitcherBlock();

    $mayer = $this->drupalCreateUser($permissions);
    $this->drupalLogin($mayer);

    $vultures = $this->createWorkspaceThroughUi('Vultures', 'vultures');
    $this->switchToWorkspace($vultures);

    $gravity = $this->createWorkspaceThroughUi('Gravity', 'gravity');

    $this->drupalGet('/admin/config/workflow/workspace/manage/' . $gravity->id() . '/activate');

    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $page->findButton('Confirm')->click();

    $page->findLink($gravity->label());
  }

}
