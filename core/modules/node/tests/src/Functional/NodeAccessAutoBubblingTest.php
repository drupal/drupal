<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the node access automatic cacheability bubbling logic.
 *
 * @group node
 * @group Cache
 * @group cacheability_safeguards
 */
class NodeAccessAutoBubblingTest extends NodeTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node_access_test', 'node_access_test_auto_bubbling'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    node_access_rebuild();

    // Create some content.
    $this->drupalCreateNode();
    $this->drupalCreateNode();
    $this->drupalCreateNode();
    $this->drupalCreateNode();
  }

  /**
   * Tests that the node grants cache context is auto-added, only when needed.
   *
   * @see node_query_node_access_alter()
   */
  public function testNodeAccessCacheabilitySafeguard() {
    $this->dumpHeaders = TRUE;

    // The node grants cache context should be added automatically.
    $this->drupalGet(new Url('node_access_test_auto_bubbling'));
    $this->assertCacheContext('user.node_grants:view');

    // The root user has the 'bypass node access' permission, which means the
    // node grants cache context is not necessary.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(new Url('node_access_test_auto_bubbling'));
    $this->assertNoCacheContext('user.node_grants:view');
    $this->drupalLogout();

    // Uninstall the module with the only hook_node_grants() implementation.
    $this->container->get('module_installer')->uninstall(['node_access_test']);
    $this->rebuildContainer();

    // Because there are no node grants defined, there also is no need for the
    // node grants cache context to be bubbled.
    $this->drupalGet(new Url('node_access_test_auto_bubbling'));
    $this->assertNoCacheContext('user.node_grants:view');
  }

}
