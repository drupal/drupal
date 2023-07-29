<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentManagerInterface;

/**
 * Tests to make sure the comment number increments properly.
 *
 * @group comment
 */
class CommentThreadingTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the comment threading.
   */
  public function testCommentThreading() {
    // Set comments to have a subject with preview disabled.
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');

    // Create a node.
    $this->drupalLogin($this->webUser);
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()]);

    // Post comment #1.
    $this->drupalLogin($this->webUser);
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();

    $comment1 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1), 'Comment #1. Comment found.');
    $this->assertEquals('01/', $comment1->getThread());
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
    $this->assertEquals('01.00/', $comment1_3->getThread());
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment1_3->id(), $comment1->id());

    // Reply to comment #1_3 creating comment #1_3_4.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment1_3->id());
    $comment1_3_4 = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1_3_4, TRUE), 'Comment #1_3_4. Second reply found.');
    $this->assertEquals('01.00.00/', $comment1_3_4->getThread());
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment1_3_4->id(), $comment1_3->id());

    // Reply to comment #1 creating comment #1_5.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment1->id());

    $comment1_5 = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment1_5), 'Comment #1_5. Third reply found.');
    $this->assertEquals('01.01/', $comment1_5->getThread());
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment1_5->id(), $comment1->id());

    // Post comment #3 overall comment #5.
    $this->drupalLogin($this->webUser);
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();

    $comment5 = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5), 'Comment #5. Second comment found.');
    $this->assertEquals('03/', $comment5->getThread());
    // Confirm that there is no link to a parent comment.
    $this->assertNoParentLink($comment5->id());

    // Reply to comment #5 creating comment #5_6.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5->id());
    $comment5_6 = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5_6, TRUE), 'Comment #6. Reply found.');
    $this->assertEquals('03.00/', $comment5_6->getThread());
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment5_6->id(), $comment5->id());

    // Reply to comment #5_6 creating comment #5_6_7.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5_6->id());
    $comment5_6_7 = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5_6_7, TRUE), 'Comment #5_6_7. Second reply found.');
    $this->assertEquals('03.00.00/', $comment5_6_7->getThread());
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment5_6_7->id(), $comment5_6->id());

    // Reply to comment #5 creating comment #5_8.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment5->id());
    $comment5_8 = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);

    // Confirm that the comment was created and has the correct threading.
    $this->assertTrue($this->commentExists($comment5_8), 'Comment #5_8. Third reply found.');
    $this->assertEquals('03.01/', $comment5_8->getThread());
    // Confirm that there is a link to the parent comment.
    $this->assertParentLink($comment5_8->id(), $comment5->id());
  }

  /**
   * Asserts that the link to the specified parent comment is present.
   *
   * @param int $cid
   *   The comment ID to check.
   * @param int $pid
   *   The expected parent comment ID.
   *
   * @internal
   */
  protected function assertParentLink(int $cid, int $pid): void {
    // This pattern matches a markup structure like:
    // @code
    // <article id="comment-2">
    //   <p>
    //     In reply to
    //     <a href="...comment-1"></a>
    //   </p>
    // </article>
    // @endcode
    $pattern = "//article[@id='comment-$cid']//p/a[contains(@href, 'comment-$pid')]";

    $this->assertSession()->elementExists('xpath', $pattern);

    // A parent link is always accompanied by the text "In reply to".
    // If we don't assert this text here, then the assertNoParentLink()
    // method is not effective.
    $pattern = "//article[@id='comment-$cid']";
    $this->assertSession()->elementTextContains('xpath', $pattern, 'In reply to');
  }

  /**
   * Asserts that the specified comment does not have a link to a parent.
   *
   * @param int $cid
   *   The comment ID to check.
   *
   * @internal
   */
  protected function assertNoParentLink(int $cid): void {
    $pattern = "//article[@id='comment-$cid']";
    // A parent link is always accompanied by the text "In reply to".
    $this->assertSession()->elementTextNotContains('xpath', $pattern, 'In reply to');
  }

}
