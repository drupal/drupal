<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentThreadingTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests comment threading.
 */
class CommentThreadingTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment Threading',
      'description' => 'Test to make sure the comment number increments properly.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests the comment threading.
   */
  function testCommentThreading() {
    // Set comments to have a subject with preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('default_mode', COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalLogout();

    // Create a node.
    $this->drupalLogin($this->web_user);
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->web_user->id()));

    // Post comment #1.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomName();
    $comment_text = $this->randomName();
    $comment1 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1), 'Comment #1. Comment found.');
    $this->assertEqual($comment1->getThread(), '01/');
    // Confirm that there is no reference to a parent comment.
    $this->assertNoParentLink($comment1->id());

    // Reply to comment #1 creating comment #2.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment1->id());
    $comment2 = $this->postComment(NULL, $this->randomName(), '', TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment2, TRUE), 'Comment #2. Reply found.');
    $this->assertEqual($comment2->getThread(), '01.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment2->id(), $comment1->id());

    // Reply to comment #2 creating comment #3.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment2->id());
    $comment3 = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment3, TRUE), 'Comment #3. Second reply found.');
    $this->assertEqual($comment3->getThread(), '01.00.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment3->id(), $comment2->id());

    // Reply to comment #1 creating comment #4.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment1->id());
    $comment4 = $this->postComment(NULL, $this->randomName(), '', TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment4), 'Comment #4. Third reply found.');
    $this->assertEqual($comment4->getThread(), '01.01/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment4->id(), $comment1->id());

    // Post comment #2 overall comment #5.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomName();
    $comment_text = $this->randomName();
    $comment5 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5), 'Comment #5. Second comment found.');
    $this->assertEqual($comment5->getThread(), '02/');
    // Confirm that there is no link to a parent comment.
    $this->assertNoParentLink($comment5->id());

    // Reply to comment #5 creating comment #6.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5->id());
    $comment6 = $this->postComment(NULL, $this->randomName(), '', TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment6, TRUE), 'Comment #6. Reply found.');
    $this->assertEqual($comment6->getThread(), '02.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment6->id(), $comment5->id());

    // Reply to comment #6 creating comment #7.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment6->id());
    $comment7 = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment7, TRUE), 'Comment #7. Second reply found.');
    $this->assertEqual($comment7->getThread(), '02.00.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment7->id(), $comment6->id());

    // Reply to comment #5 creating comment #8.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5->id());
    $comment8 = $this->postComment(NULL, $this->randomName(), '', TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment8), 'Comment #8. Third reply found.');
    $this->assertEqual($comment8->getThread(), '02.01/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment8->id(), $comment5->id());
  }

  /**
   * Asserts that the link to the specified parent comment is present.
   *
   * @parm int $cid
   *   The comment ID to check.
   * @param int $pid
   *   The expected parent comment ID.
   */
  protected function assertParentLink($cid, $pid) {
    // This pattern matches a markup structure like:
    // <a id="comment-2"></a>
    // <article>
    //   <p class="parent">
    //     <a href="...comment-1"></a>
    //   </p>
    //  </article>
    $pattern = "//a[@id='comment-$cid']/following-sibling::article//p[contains(@class, 'parent')]//a[contains(@href, 'comment-$pid')]";

    $this->assertFieldByXpath($pattern, NULL, format_string(
      'Comment %cid has a link to parent %pid.',
      array(
        '%cid' => $cid,
        '%pid' => $pid,
      )
    ));
  }

  /**
   * Asserts that the specified comment does not have a link to a parent.
   *
   * @parm int $cid
   *   The comment ID to check.
   */
  protected function assertNoParentLink($cid) {
    // This pattern matches a markup structure like:
    // <a id="comment-2"></a>
    // <article>
    //   <p class="parent"></p>
    //  </article>

    $pattern = "//a[@id='comment-$cid']/following-sibling::article//p[contains(@class, 'parent')]";
    $this->assertNoFieldByXpath($pattern, NULL, format_string(
      'Comment %cid does not have a link to a parent.',
      array(
        '%cid' => $cid,
      )
    ));
  }

}
