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
class NodeAccessCacheabilityTest extends NodeTestBase {

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

  /**
   * Tests that the user cache contexts are correctly set.
   */
  public function testNodeAccessCacheContext() {
    // Create a user, with edit/delete own content permission.
    $test_user1 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
    ]);

    $this->drupalLogin($test_user1);

    $node1 = $this->createNode(['type' => 'page']);

    // User should be able to edit/delete their own content.
    // Therefore after the access check in node_node_access the user cache
    // context should be added.
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->assertCacheContext('user');
    $this->drupalGet('node/' . $node1->id() . '/delete');
    $this->assertCacheContext('user');

    // Create a user without edit/delete permission.
    $test_user2 = $this->drupalCreateUser([
      'access content',
    ]);

    $this->drupalLogin($test_user2);

    $node2 = $this->createNode(['type' => 'page']);

    // The user shouldn't have access to the node edit/delete pages.
    // Therefore after the access check in node_node_access the user permissions
    // cache context should be added.
    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->assertCacheContext('user.permissions');
    $this->drupalGet('node/' . $node2->id() . '/delete');
    $this->assertCacheContext('user.permissions');
  }

}
