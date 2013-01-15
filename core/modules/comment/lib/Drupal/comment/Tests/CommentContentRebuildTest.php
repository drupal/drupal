<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentContentRebuildTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests comment content rebuilding.
 */
class CommentContentRebuildTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment Rebuild',
      'description' => 'Test to make sure the comment content is rebuilt.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests the rebuilding of comment's content arrays on calling comment_view().
   */
  function testCommentRebuild() {
    // Update the comment settings so preview isn't required.
    $this->drupalLogin($this->admin_user);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_OPTIONAL);
    $this->drupalLogout();

    // Log in as the web user and add the comment.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomName();
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    $this->assertTrue($this->commentExists($comment), 'Comment found.');

    // Add the property to the content array and then see if it still exists on build.
    $comment->content['test_property'] = array('#value' => $this->randomString());
    $built_content = comment_view($comment);

    // This means that the content was rebuilt as the added test property no longer exists.
    $this->assertFalse(isset($built_content['test_property']), 'Comment content was emptied before being built.');
  }
}
