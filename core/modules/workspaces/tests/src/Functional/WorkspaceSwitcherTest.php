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
  protected static $modules = ['block', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
   * Tests switching workspace via the switcher block and admin page.
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
   * Tests switching workspace via a query parameter.
   */
  public function testQueryParameterNegotiator() {
    $web_assert = $this->assertSession();
    // Initially the default workspace should be active.
    $web_assert->elementContains('css', '.block-workspace-switcher', 'None');

    // When adding a query parameter the workspace will be switched.
    $current_user_url = \Drupal::currentUser()->getAccount()->toUrl();
    $this->drupalGet($current_user_url, ['query' => ['workspace' => 'stage']]);
    $web_assert->elementContains('css', '.block-workspace-switcher', 'Stage');

    // The workspace switching via query parameter should persist.
    $this->drupalGet($current_user_url);
    $web_assert->elementContains('css', '.block-workspace-switcher', 'Stage');

    // Check that WorkspaceCacheContext provides the cache context used to
    // support its functionality.
    $this->assertCacheContext('session');
  }

}
