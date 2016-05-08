<?php

namespace Drupal\comment\Tests;

/**
 * Tests to ensure that appropriate and accessible markup is created for comment
 * titles.
 *
 * @group comment
 */
class CommentTitleTest extends CommentTestBase {
  /**
   * Tests markup for comments with empty titles.
   */
  public function testCommentEmptyTitles() {
    // Installs module that sets comments to an empty string.
    \Drupal::service('module_installer')->install(array('comment_empty_title_test'));

    // Set comments to have a subject with preview disabled.
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);

    // Create a node.
    $this->drupalLogin($this->webUser);
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()));

    // Post comment #1 and verify that h3's are not rendered.
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created.
    $regex = '/<a id="comment-' . $comment->id() . '"(.*?)';
    $regex .= $comment->comment_body->value . '(.*?)';
    $regex .= '/s';
    $this->assertPattern($regex, 'Comment is created successfully');
    // Tests that markup is not generated for the comment without header.
    $this->assertNoPattern('|<h3[^>]*></h3>|', 'Comment title H3 element not found when title is an empty string.');
  }

  /**
   * Tests markup for comments with populated titles.
   */
  public function testCommentPopulatedTitles() {
    // Set comments to have a subject with preview disabled.
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);

    // Create a node.
    $this->drupalLogin($this->webUser);
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()));

    // Post comment #1 and verify that title is rendered in h3.
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();
    $comment1 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created.
    $this->assertTrue($this->commentExists($comment1), 'Comment #1. Comment found.');
    // Tests that markup is created for comment with heading.
    $this->assertPattern('|<h3[^>]*><a[^>]*>' . $subject_text . '</a></h3>|', 'Comment title is rendered in h3 when title populated.');
  }

}
