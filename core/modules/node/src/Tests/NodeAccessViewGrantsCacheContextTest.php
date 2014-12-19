<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeAccessViewGrantsCacheContextTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the node access view grants cache context service.
 *
 * @group node
 * @group Cache
 */
class NodeAccessViewGrantsCacheContextTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_access_test');

  /**
   * User with permission to view content.
   */
  protected $accessUser;

  /**
   * User without permission to view content.
   */
  protected $noAccessUser;

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

    // Create user with simple node access permission. The 'node test view'
    // permission is implemented and granted by the node_access_test module.
    $this->accessUser = $this->drupalCreateUser(array('access content overview', 'access content', 'node test view'));
    $this->noAccessUser = $this->drupalCreateUser(array('access content overview', 'access content'));
    $this->noAccessUser2 = $this->drupalCreateUser(array('access content overview', 'access content'));

    $this->userMapping = [
      1 => $this->root_user,
      2 => $this->accessUser,
      3 => $this->noAccessUser,
    ];
  }

  /**
   * Asserts that for each given user, the expected cache context is returned.
   *
   * @param array $expected
   *   Expected values, keyed by user ID, expected cache contexts as values.
   */
  protected function assertCacheContext(array $expected) {
    foreach ($expected as $uid => $context) {
      if ($uid > 0) {
        $this->drupalLogin($this->userMapping[$uid]);
      }
      $this->pass('Asserting cache context for user ' . $uid . '.');
      $this->assertIdentical($context, $this->container->get('cache_context.node_view_grants')->getContext());
    }
    $this->drupalLogout();
  }

  /**
   * Tests NodeAccessViewGrantsCacheContext::getContext().
   */
  public function testCacheContext() {
    $this->assertCacheContext([
      0 => 'all:0;node_access_test_author:0;node_access_all:0',
      1 => 'all',
      2 => 'all:0;node_access_test_author:2;node_access_test:8888,8889',
      3 => 'all:0;node_access_test_author:3',
    ]);

    // Grant view to all nodes (because nid = 0) for users in the
    // 'node_access_all' realm.
    $record = array(
      'nid' => 0,
      'gid' => 0,
      'realm' => 'node_access_all',
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    );
    db_insert('node_access')->fields($record)->execute();

    // Put user accessUser (uid 0) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', 0);
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertCacheContext([
      0 => 'all',
      1 => 'all',
      2 => 'all:0;node_access_test_author:2;node_access_test:8888,8889',
      3 => 'all:0;node_access_test_author:3',
    ]);

    // Put user accessUser (uid 2) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', $this->accessUser->id());
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertCacheContext([
      0 => 'all:0;node_access_test_author:0',
      1 => 'all',
      2 => 'all',
      3 => 'all:0;node_access_test_author:3',
    ]);

    // Put user noAccessUser (uid 3) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', $this->noAccessUser->id());
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertCacheContext([
      0 => 'all:0;node_access_test_author:0',
      1 => 'all',
      2 => 'all:0;node_access_test_author:2;node_access_test:8888,8889',
      3 => 'all',
    ]);

    // Uninstall the node_access_test module
    $this->container->get('module_installer')->uninstall(['node_access_test']);
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertCacheContext([
      0 => 'all',
      1 => 'all',
      2 => 'all',
      3 => 'all',
    ]);
  }

}
