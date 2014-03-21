<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAccessPagerTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\Language;
use Drupal\comment\CommentInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests pagination with a node access module enabled.
 */
class NodeAccessPagerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_access_test', 'comment', 'forum');

  public static function getInfo() {
    return array(
      'name' => 'Node access pagination',
      'description' => 'Test access controlled node views have the right amount of comment pages.',
      'group' => 'Node',
    );
  }

  public function setUp() {
    parent::setUp();

    node_access_rebuild();
    $this->drupalCreateContentType(array('type' => 'page', 'name' => t('Basic page')));
    $this->container->get('comment.manager')->addDefaultField('node', 'page');
    $this->web_user = $this->drupalCreateUser(array('access content', 'access comments', 'node test view'));
  }

  /**
   * Tests the comment pager for nodes with multiple grants per realm.
   */
  public function testCommentPager() {
    // Create a node.
    $node = $this->drupalCreateNode();

    // Create 60 comments.
    for ($i = 0; $i < 60; $i++) {
      $comment = entity_create('comment', array(
        'entity_id' => $node->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'subject' => $this->randomName(),
        'comment_body' => array(
          array('value' => $this->randomName()),
        ),
        'status' => CommentInterface::PUBLISHED,
      ));
      $comment->save();
    }

    $this->drupalLogin($this->web_user);

    // View the node page. With the default 50 comments per page there should
    // be two pages (0, 1) but no third (2) page.
    $this->drupalGet('node/' . $node->id());
    $this->assertText($node->label());
    $this->assertText(t('Comments'));
    $this->assertRaw('page=1');
    $this->assertNoRaw('page=2');
  }

  /**
   * Tests the forum node pager for nodes with multiple grants per realm.
   */
  public function testForumPager() {
    // Look up the forums vocabulary ID.
    $vid = \Drupal::config('forum.settings')->get('vocabulary');
    $this->assertTrue($vid, 'Forum navigation vocabulary ID is set.');

    // Look up the general discussion term.
    $tree = taxonomy_get_tree($vid, 0, 1);
    $tid = reset($tree)->tid;
    $this->assertTrue($tid, 'General discussion term is found in the forum vocabulary.');

    // Create 30 nodes.
    for ($i = 0; $i < 30; $i++) {
      $this->drupalCreateNode(array(
        'nid' => NULL,
        'type' => 'forum',
        'taxonomy_forums' => array(
          array('target_id' => $tid),
        ),
      ));
    }

    // View the general discussion forum page. With the default 25 nodes per
    // page there should be two pages for 30 nodes, no more.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('forum/' . $tid);
    $this->assertRaw('page=1');
    $this->assertNoRaw('page=2');
  }
}
