<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentThreadingTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentManagerInterface;

/**
 * Tests to make sure the comment number increments properly.
 *
 * @group comment
 */
class CommentThreadingTest extends CommentTestBase {
  /**
   * Tests the comment threading.
   */
  function testCommentThreading() {
    // Set comments to have a subject with preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalLogout();

    // Create a node.
    $this->drupalLogin($this->web_user);
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->web_user->id()));

    // Post comment #1.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();

    $comment1 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1), 'Comment #1. Comment found.');
    $this->assertEqual($comment1->getThread(), '01/');
    // Confirm that there is no reference to a parent comment.
    $this->assertNoParentLink($comment1->id());

    // Post comment #2 following the comment #1 to test if it correctly jumps
    // out the indentation in case there is a thread above.
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();
    $this->postComment($this->node, $comment_text, $subject_text, TRUE);

    // Reply to comment #1 creating comment #1_3.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment1->id());
    $comment1_3 = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1_3, TRUE), 'Comment #1_3. Reply found.');
    $this->assertEqual($comment1_3->getThread(), '01.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment1_3->id(), $comment1->id());


    // Reply to comment #1_3 creating comment #1_3_4.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment1_3->id());
    $comment1_3_4 = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1_3_4, TRUE), 'Comment #1_3_4. Second reply found.');
    $this->assertEqual($comment1_3_4->getThread(), '01.00.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment1_3_4->id(), $comment1_3->id());

    // Reply to comment #1 creating comment #1_5.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment1->id());

    $comment1_5 = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1_5), 'Comment #1_5. Third reply found.');
    $this->assertEqual($comment1_5->getThread(), '01.01/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment1_5->id(), $comment1->id());

    // Post comment #3 overall comment #5.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();

    $comment5 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5), 'Comment #5. Second comment found.');
    $this->assertEqual($comment5->getThread(), '03/');
    // Confirm that there is no link to a parent comment.
    $this->assertNoParentLink($comment5->id());

    // Reply to comment #5 creating comment #5_6.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5->id());
    $comment5_6 = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5_6, TRUE), 'Comment #6. Reply found.');
    $this->assertEqual($comment5_6->getThread(), '03.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment5_6->id(), $comment5->id());

    // Reply to comment #5_6 creating comment #5_6_7.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5_6->id());
    $comment5_6_7 = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5_6_7, TRUE), 'Comment #5_6_7. Second reply found.');
    $this->assertEqual($comment5_6_7->getThread(), '03.00.00/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment5_6_7->id(), $comment5_6->id());

    // Reply to comment #5 creating comment #5_8.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5->id());
    $comment5_8 = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5_8), 'Comment #5_8. Third reply found.');
    $this->assertEqual($comment5_8->getThread(), '03.01/');
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment5_8->id(), $comment5->id());
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
