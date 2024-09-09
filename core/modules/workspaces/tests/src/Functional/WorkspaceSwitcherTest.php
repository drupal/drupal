<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
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
  protected static $modules = [
    'block',
    'dynamic_page_cache',
    'node',
    'toolbar',
    'workspaces',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  public function testSwitchingWorkspaces(): void {
    $this->createAndActivateWorkspaceThroughUi('Vultures', 'vultures');
    $gravity = $this->createWorkspaceThroughUi('Gravity', 'gravity');

    // Confirm the block shows on the front page.
    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasContent('Workspace switcher'));

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
  public function testQueryParameterNegotiator(): void {
    $web_assert = $this->assertSession();
    // Initially the default workspace should be active.
    $web_assert->elementContains('css', '#block-workspace-switcher', 'None');

    // When adding a query parameter the workspace will be switched.
    $current_user_url = \Drupal::currentUser()->getAccount()->toUrl();
    $this->drupalGet($current_user_url, ['query' => ['workspace' => 'stage']]);
    $web_assert->elementContains('css', '#block-workspace-switcher', 'Stage');

    // The workspace switching via query parameter should persist.
    $this->drupalGet($current_user_url);
    $web_assert->elementContains('css', '#block-workspace-switcher', 'Stage');

    // Check that WorkspaceCacheContext provides the cache context used to
    // support its functionality.
    $this->assertCacheContext('session');
  }

  /**
   * Tests that the toolbar workspace switcher doesn't disable the page cache.
   */
  public function testToolbarSwitcherDynamicPageCache(): void {
    $node_type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(['type' => $node_type->id()]);
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar',
      'view any workspace',
    ]));
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'MISS');
    // Reload the page, it should be cached now.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementExists('css', '.workspaces-toolbar-tab');
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');
  }

}
