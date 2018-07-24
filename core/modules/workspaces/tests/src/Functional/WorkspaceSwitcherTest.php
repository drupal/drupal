<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests workspace switching functionality.
 *
 * @group workspaces
 */
class WorkspaceSwitcherTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'workspaces'];

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

    $this->drupalGet('/admin/config/workflow/workspaces/manage/' . $gravity->id() . '/activate');

    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $page->findButton('Confirm')->click();

    // Check that WorkspaceCacheContext provides the cache context used to
    // support its functionality.
    $this->assertCacheContext('session');

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

    // Check that WorkspaceCacheContext provides the cache context used to
    // support its functionality.
    $this->assertCacheContext('session');
  }

}
