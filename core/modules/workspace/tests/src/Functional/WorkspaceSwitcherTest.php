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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'create workspace',
      'edit own workspace',
      'view own workspace',
      'bypass entity access own workspace',
    ];

    $this->setupWorkspaceSwitcherBlock();

    $mayer = $this->drupalCreateUser($permissions);
    $this->drupalLogin($mayer);
  }

  /**
   * Test switching workspace via the switcher block and admin page.
   */
  public function testSwitchingWorkspaces() {
    $vultures = $this->createWorkspaceThroughUi('Vultures', 'vultures');
    $this->switchToWorkspace($vultures);

    $gravity = $this->createWorkspaceThroughUi('Gravity', 'gravity');

    $this->drupalGet('/admin/config/workflow/workspace/manage/' . $gravity->id() . '/activate');

    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $page->findButton('Confirm')->click();

    $page->findLink($gravity->label());
  }

  /**
   * Test switching workspace via a query parameter.
   */
  public function testQueryParameterNegotiator() {
    $web_assert = $this->assertSession();
    // Initially the default workspace should be active.
    $web_assert->elementContains('css', '.block-workspace-switcher', 'Live');

    // When adding a query parameter the workspace will be switched.
    $this->drupalGet('<front>', ['query' => ['workspace' => 'stage']]);
    $web_assert->elementContains('css', '.block-workspace-switcher', 'Stage');

    // The workspace switching via query parameter should persist.
    $this->drupalGet('<front>');
    $web_assert->elementContains('css', '.block-workspace-switcher', 'Stage');
  }

}
