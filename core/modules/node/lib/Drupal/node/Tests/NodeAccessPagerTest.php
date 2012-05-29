<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAccessPagerTest.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests pagination with a node access module enabled.
 */
class NodeAccessPagerTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Node access pagination',
      'description' => 'Test access controlled node views have the right amount of comment pages.',
      'group' => 'Node',
    );
  }

  public function setUp() {
    parent::setUp('node_access_test', 'comment', 'forum');
    node_access_rebuild();
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
        'nid' => $node->nid,
        'subject' => $this->randomName(),
        'comment_body' => array(
          LANGUAGE_NOT_SPECIFIED => array(
            array('value' => $this->randomName()),
          ),
        ),
      ));
      $comment->save();
    }

    $this->drupalLogin($this->web_user);

    // View the node page. With the default 50 comments per page there should
    // be two pages (0, 1) but no third (2) page.
    $this->drupalGet('node/' . $node->nid);
    $this->assertText($node->title, t('Node title found.'));
    $this->assertText(t('Comments'), t('Has a comments section.'));
    $this->assertRaw('page=1', t('Secound page exists.'));
    $this->assertNoRaw('page=2', t('No third page exists.'));
  }

  /**
   * Tests the forum node pager for nodes with multiple grants per realm.
   */
  public function testForumPager() {
    // Lookup the forums vocabulary vid.
    $vid = variable_get('forum_nav_vocabulary', 0);
    $this->assertTrue($vid, t('Forum navigation vocabulary found.'));

    // Lookup the general discussion term.
    $tree = taxonomy_get_tree($vid, 0, 1);
    $tid = reset($tree)->tid;
    $this->assertTrue($tid, t('General discussion term found.'));

    // Create 30 nodes.
    for ($i = 0; $i < 30; $i++) {
      $this->drupalCreateNode(array(
        'nid' => NULL,
        'type' => 'forum',
        'taxonomy_forums' => array(
          LANGUAGE_NOT_SPECIFIED => array(
            array('tid' => $tid, 'vid' => $vid, 'vocabulary_machine_name' => 'forums'),
          ),
        ),
      ));
    }

    // View the general discussion forum page. With the default 25 nodes per
    // page there should be two pages for 30 nodes, no more.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('forum/' . $tid);
    $this->assertRaw('page=1', t('Secound page exists.'));
    $this->assertNoRaw('page=2', t('No third page exists.'));
  }
}
