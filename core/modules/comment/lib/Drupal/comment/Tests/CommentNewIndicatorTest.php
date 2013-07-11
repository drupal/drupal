<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentNewIndicatorTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests the 'new' marker on comments.
 */
class CommentNewIndicatorTest extends CommentTestBase {

  /**
   * Use the main node listing to test rendering on teasers.
   *
   * @var array
   *
   * @todo Remove this dependency.
   */
  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => "Comment 'new' indicator",
      'description' => "Tests the 'new' indicator posted on comments.",
      'group' => 'Comment',
    );
  }

  /**
   * Tests new comment marker.
   */
  public function testCommentNewCommentsIndicator() {
    // Test if the right links are displayed when no comment is present for the
    // node.
    $this->drupalLogin($this->admin_user);
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => COMMENT_NODE_OPEN));
    $this->drupalGet('node');
    $this->assertNoLink(t('@count comments', array('@count' => 0)));
    $this->assertNoLink(t('@count new comments', array('@count' => 0)));
    $this->assertLink(t('Read more'));

    // Create a new comment. This helper function may be run with different
    // comment settings so use $comment->save() to avoid complex setup.
    $comment = entity_create('comment', array(
      'cid' => NULL,
      'nid' => $this->node->nid,
      'node_type' => $this->node->type,
      'pid' => 0,
      'uid' => $this->loggedInUser->id(),
      'status' => COMMENT_PUBLISHED,
      'subject' => $this->randomName(),
      'hostname' => '127.0.0.1',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'comment_body' => array(Language::LANGCODE_NOT_SPECIFIED => array($this->randomName())),
    ));
    $comment->save();
    $this->drupalLogout();

    // Log in with 'web user' and check comment links.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('node');
    $this->assertLink(t('1 new comment'));
    $this->clickLink(t('1 new comment'));
    $this->assertRaw('<a id="new"></a>', 'Found "new" marker.');
    $this->assertTrue($this->xpath('//a[@id=:new]/following-sibling::a[1][@id=:comment_id]', array(':new' => 'new', ':comment_id' => 'comment-1')), 'The "new" anchor is positioned at the right comment.');

    // Test if "new comment" link is correctly removed.
    $this->drupalGet('node');
    $this->assertLink(t('1 comment'));
    $this->assertLink(t('Read more'));
    $this->assertNoLink(t('1 new comment'));
    $this->assertNoLink(t('@count new comments', array('@count' => 0)));
  }

}
