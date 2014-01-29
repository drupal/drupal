<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodePageCacheTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the cache invalidation of node operations.
 */
class NodePageCacheTest extends NodeTestBase {

  /**
   * An admin user with administrative permissions for nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => 'Node page cache test',
      'description' => 'Test cache invalidation of node operations.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    $this->container->get('config.factory')->get('system.performance')
      ->set('cache.page.use_internal', 1)
      ->set('cache.page.max_age', 300)
      ->save();

    $this->adminUser = $this->drupalCreateUser(array(
      'bypass node access',
      'access content overview',
      'administer nodes',
    ));
  }

  /**
   * Tests deleting nodes clears page cache.
   */
  public function testNodeDelete() {
    $author = $this->drupalCreateUser();
    $node_path = 'node/' . $this->drupalCreateNode(array('uid' => $author->id()))->id();

    // Populate page cache.
    $this->drupalGet($node_path);

    // Verify the presence of the correct cache tags.
    $cid_parts = array(url($node_path, array('absolute' => TRUE)), 'html');
    $cid = sha1(implode(':', $cid_parts));
    $cache_entry = \Drupal::cache('page')->get($cid);
    $this->assertIdentical($cache_entry->tags, array('content:1', 'user:' . $author->id(), 'filter_format:plain_text'));

    // Login and delete the node.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($node_path . '/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));

    // Logout and check the node is not available.
    $this->drupalLogout();
    $this->drupalGet($node_path);
    $this->assertResponse(404);

    // Create two new nodes.
    $this->drupalCreateNode();
    $node_path = 'node/' . $this->drupalCreateNode()->id();

    // Populate page cache.
    $this->drupalGet($node_path);

    // Login and delete the nodes.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');
    $edit = array(
      'action' => 'node_delete_action',
      'node_bulk_form[0]' => 1,
      'node_bulk_form[1]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Delete'));

    // Logout and check the node is not available.
    $this->drupalLogout();
    $this->drupalGet($node_path);
    $this->assertResponse(404);
  }

}
