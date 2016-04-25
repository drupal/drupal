<?php

namespace Drupal\forum\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the forum index listing.
 *
 * @group forum
 */
class ForumIndexTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'comment', 'forum');

  protected function setUp() {
    parent::setUp();

    // Create a test user.
    $web_user = $this->drupalCreateUser(['create forum content', 'edit own forum content', 'edit any forum content', 'administer nodes', 'administer forums']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the forum index for published and unpublished nodes.
   */
  function testForumIndexStatus() {
    // The forum ID to use.
    $tid = 1;

    // Create a test node.
    $title = $this->randomMachineName(20);
    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => $this->randomMachineName(200),
    );

    // Create the forum topic, preselecting the forum ID via a URL parameter.
    $this->drupalGet("forum/$tid");
    $this->clickLink(t('Add new @node_type', array('@node_type' => 'Forum topic')));
    $this->assertUrl('node/add/forum', array('query' => array('forum_id' => $tid)));
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertTrue(!empty($node), 'New forum node found in database.');

    // Create a child forum.
    $edit = array(
      'name[0][value]' => $this->randomMachineName(20),
      'description[0][value]' => $this->randomMachineName(200),
      'parent[0]' => $tid,
    );
    $this->drupalPostForm('admin/structure/forum/add/forum', $edit, t('Save'));
    $tid_child = $tid + 1;

    // Verify that the node appears on the index.
    $this->drupalGet('forum/' . $tid);
    $this->assertText($title, 'Published forum topic appears on index.');
    $this->assertCacheTag('node_list');
    $this->assertCacheTag('config:node.type.forum');
    $this->assertCacheTag('comment_list');
    $this->assertCacheTag('node:' . $node->id());
    $this->assertCacheTag('taxonomy_term:' . $tid);
    $this->assertCacheTag('taxonomy_term:' . $tid_child);


    // Unpublish the node.
    $this->drupalPostForm('node/' . $node->id() . '/edit', array(), t('Save and unpublish'));
    $this->drupalGet('node/' . $node->id());
    $this->assertText(t('Access denied'), 'Unpublished node is no longer accessible.');

    // Verify that the node no longer appears on the index.
    $this->drupalGet('forum/' . $tid);
    $this->assertNoText($title, 'Unpublished forum topic no longer appears on index.');
  }
}
