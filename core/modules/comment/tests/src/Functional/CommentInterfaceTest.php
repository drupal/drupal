<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\Core\Url;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\user\RoleInterface;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests comment user interfaces.
 *
 * @group comment
 */
class CommentInterfaceTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Set up comments to have subject and preview disabled.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    // Make sure that comment field title is not displayed when there's no
    // comments posted.
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->responseNotMatches('@<h2[^>]*>Comments</h2>@');

    // Set comments to have subject and preview disabled.
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(FALSE);
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalLogout();
  }

  /**
   * Tests the comment interface.
   */
  public function testCommentInterface() {

    // Post comment #1 without subject or preview.
    $this->drupalLogin($this->webUser);
    $comment_text = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text);
    $this->assertTrue($this->commentExists($comment), 'Comment found.');

    // Test that using an invalid entity-type does not raise an error.
    $this->drupalGet('comment/reply/yeah-this-is-not-an-entity-type/' . $this->node->id() . '/comment/' . $comment->id());
    $this->assertSession()->statusCodeEquals(404);

    // Test the comment field title is displayed when there's comments.
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->responseMatches('@<h2[^>]*>Comments</h2>@');

    // Set comments to have subject and preview to required.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_REQUIRED);
    $this->drupalLogout();

    // Create comment #2 that allows subject and requires preview.
    $this->drupalLogin($this->webUser);
    $subject_text = $this->randomMachineName();
    $comment_text = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    $this->assertTrue($this->commentExists($comment), 'Comment found.');

    // Comment as anonymous with preview required.
    $this->drupalLogout();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access content', 'access comments', 'post comments', 'skip comment approval']);
    $anonymous_comment = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $this->assertTrue($this->commentExists($anonymous_comment), 'Comment found.');
    $anonymous_comment->delete();

    // Check comment display.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->pageTextContains($subject_text);
    $this->assertSession()->pageTextContains($comment_text);
    $arguments = [
      ':link' => base_path() . 'comment/' . $comment->id() . '#comment-' . $comment->id(),
    ];
    $pattern_permalink = '//footer[contains(@class,"comment__meta")]/a[contains(@href,:link) and text()="Permalink"]';
    $permalink = $this->xpath($pattern_permalink, $arguments);
    $this->assertNotEmpty($permalink, 'Permalink link found.');

    // Set comments to have subject and preview to optional.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_OPTIONAL);

    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $this->assertSession()->titleEquals('Edit comment ' . $comment->getSubject() . ' | Drupal');

    // Test changing the comment author to "Anonymous".
    $comment = $this->postComment(NULL, $comment->comment_body->value, $comment->getSubject(), ['uid' => '']);
    $this->assertSame('Anonymous', $comment->getAuthorName());
    $this->assertEquals(0, $comment->getOwnerId());

    // Test changing the comment author to an unverified user.
    $random_name = $this->randomMachineName();
    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $comment = $this->postComment(NULL, $comment->comment_body->value, $comment->getSubject(), ['name' => $random_name]);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->pageTextContains($random_name . ' (not verified)');

    // Test changing the comment author to a verified user.
    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $comment = $this->postComment(NULL, $comment->comment_body->value, $comment->getSubject(), ['uid' => $this->webUser->getAccountName() . ' (' . $this->webUser->id() . ')']);
    $this->assertSame($this->webUser->getAccountName(), $comment->getAuthorName());
    $this->assertSame($this->webUser->id(), $comment->getOwnerId());

    $this->drupalLogout();

    // Reply to comment #2 creating comment #3 with optional preview and no
    // subject though field enabled.
    $this->drupalLogin($this->webUser);
    // Deliberately use the wrong url to test
    // \Drupal\comment\Controller\CommentController::redirectNode().
    $this->drupalGet('comment/' . $this->node->id() . '/reply');
    // Verify we were correctly redirected.
    $this->assertSession()->addressEquals(Url::fromRoute('comment.reply', ['entity_type' => 'node', 'entity' => $this->node->id(), 'field_name' => 'comment']));
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment->id());
    $this->assertSession()->pageTextContains($subject_text);
    $this->assertSession()->pageTextContains($comment_text);
    $reply = $this->postComment(NULL, $this->randomMachineName(), '', TRUE);
    $reply_loaded = Comment::load($reply->id());
    $this->assertTrue($this->commentExists($reply, TRUE), 'Reply found.');
    $this->assertEquals($comment->id(), $reply_loaded->getParentComment()->id(), 'Pid of a reply to a comment is set correctly.');
    // Check the thread of reply grows correctly.
    $this->assertEquals(rtrim($comment->getThread(), '/') . '.00/', $reply_loaded->getThread());

    // Second reply to comment #2 creating comment #4.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment->id());
    $this->assertSession()->pageTextContains($comment->getSubject());
    $this->assertSession()->pageTextContains($comment->comment_body->value);
    $reply = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $reply_loaded = Comment::load($reply->id());
    $this->assertTrue($this->commentExists($reply, TRUE), 'Second reply found.');
    // Check the thread of second reply grows correctly.
    $this->assertEquals(rtrim($comment->getThread(), '/') . '.01/', $reply_loaded->getThread());

    // Reply to comment #4 creating comment #5.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $reply_loaded->id());
    $this->assertSession()->pageTextContains($reply_loaded->getSubject());
    $this->assertSession()->pageTextContains($reply_loaded->comment_body->value);
    $reply = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $reply_loaded = Comment::load($reply->id());
    $this->assertTrue($this->commentExists($reply, TRUE), 'Second reply found.');
    // Check the thread of reply to second reply grows correctly.
    $this->assertEquals(rtrim($comment->getThread(), '/') . '.01.00/', $reply_loaded->getThread());

    // Edit reply.
    $this->drupalGet('comment/' . $reply->id() . '/edit');
    $reply = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Modified reply found.');

    // Confirm a new comment is posted to the correct page.
    $this->setCommentsPerPage(2);
    $comment_new_page = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $this->assertTrue($this->commentExists($comment_new_page), 'Page one exists. %s');
    $this->drupalGet('node/' . $this->node->id(), ['query' => ['page' => 2]]);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Page two exists. %s');
    $this->setCommentsPerPage(50);

    // Attempt to reply to an unpublished comment.
    $reply_loaded->setUnpublished();
    $reply_loaded->save();
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $reply_loaded->id());
    $this->assertSession()->statusCodeEquals(403);

    // Attempt to post to node with comments disabled.
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'comment' => [['status' => CommentItemInterface::HIDDEN]]]);
    $this->assertNotNull($this->node, 'Article node created.');
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->fieldNotExists('edit-comment');

    // Attempt to post to node with read-only comments.
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'comment' => [['status' => CommentItemInterface::CLOSED]]]);
    $this->assertNotNull($this->node, 'Article node created.');
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->fieldNotExists('edit-comment');

    // Attempt to post to node with comments enabled (check field names etc).
    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'comment' => [['status' => CommentItemInterface::OPEN]]]);
    $this->assertNotNull($this->node, 'Article node created.');
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertSession()->pageTextNotContains('This discussion is closed');
    // Ensure that the comment body field exists.
    $this->assertSession()->fieldExists('edit-comment-body-0-value');

    // Delete comment and make sure that reply is also removed.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->deleteComment($comment);
    $this->deleteComment($comment_new_page);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertFalse($this->commentExists($comment), 'Comment not found.');
    $this->assertFalse($this->commentExists($reply, TRUE), 'Reply not found.');

    // Enabled comment form on node page.
    $this->drupalLogin($this->adminUser);
    $this->setCommentForm(TRUE);
    $this->drupalLogout();

    // Submit comment through node form.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->node->id());
    $form_comment = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $this->assertTrue($this->commentExists($form_comment), 'Form comment found.');

    // Disable comment form on node page.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->setCommentForm(FALSE);
  }

  /**
   * Tests that the subject is automatically filled if disabled or left blank.
   *
   * When the subject field is blank or disabled, the first 29 characters of the
   * comment body are used for the subject. If this would break within a word,
   * then the break is put at the previous word boundary instead.
   */
  public function testAutoFilledSubject() {
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->node->id());

    // Break when there is a word boundary before 29 characters.
    $body_text = 'Lorem ipsum Lorem ipsum Loreming ipsum Lorem ipsum';
    $comment1 = $this->postComment(NULL, $body_text, '', TRUE);
    $this->assertTrue($this->commentExists($comment1), 'Form comment found.');
    $this->assertEquals('Lorem ipsum Lorem ipsum…', $comment1->getSubject());

    // Break at 29 characters where there's no boundary before that.
    $body_text2 = 'LoremipsumloremipsumLoremingipsumLoremipsum';
    $comment2 = $this->postComment(NULL, $body_text2, '', TRUE);
    $this->assertEquals('LoremipsumloremipsumLoreming…', $comment2->getSubject());
  }

  /**
   * Tests that automatic subject is correctly created from HTML comment text.
   *
   * This is the same test as in CommentInterfaceTest::testAutoFilledSubject()
   * with the additional check that HTML is stripped appropriately prior to
   * character-counting.
   */
  public function testAutoFilledHtmlSubject() {
    // Set up two default (i.e. filtered HTML) input formats, because then we
    // can select one of them. Then create a user that can use these formats,
    // log the user in, and then GET the node page on which to test the
    // comments.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    ]);
    $filtered_html_format->save();
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $full_html_format->save();
    $html_user = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'edit own comments',
      'skip comment approval',
      'access content',
      $filtered_html_format->getPermissionName(),
      $full_html_format->getPermissionName(),
    ]);
    $this->drupalLogin($html_user);
    $this->drupalGet('node/' . $this->node->id());

    // HTML should not be included in the character count.
    $body_text1 = '<span></span><strong> </strong><span> </span><strong></strong>Hello World<br />';
    $edit1 = [
      'comment_body[0][value]' => $body_text1,
      'comment_body[0][format]' => 'filtered_html',
    ];
    $this->submitForm($edit1, 'Save');
    $this->assertEquals('Hello World', Comment::load(1)->getSubject());

    // If there's nothing other than HTML, the subject should be '(No subject)'.
    $body_text2 = '<span></span><strong> </strong><span> </span><strong></strong> <br />';
    $edit2 = [
      'comment_body[0][value]' => $body_text2,
      'comment_body[0][format]' => 'filtered_html',
    ];
    $this->submitForm($edit2, 'Save');
    $this->assertEquals('(No subject)', Comment::load(2)->getSubject());
  }

  /**
   * Tests the comment formatter configured with a custom comment view mode.
   */
  public function testViewMode() {
    $this->drupalLogin($this->webUser);
    $this->drupalGet($this->node->toUrl());
    $comment_text = $this->randomMachineName();
    // Post a comment.
    $this->postComment($this->node, $comment_text);

    // Comment displayed in 'default' display mode found and has body text.
    $comment_element = $this->cssSelect('.comment-wrapper');
    $this->assertNotEmpty($comment_element);
    $this->assertSession()->responseContains('<p>' . $comment_text . '</p>');

    // Create a new comment entity view mode.
    $mode = mb_strtolower($this->randomMachineName());
    EntityViewMode::create([
      'targetEntityType' => 'comment',
      'id' => "comment.$mode",
    ])->save();
    // Create the corresponding entity view display for article node-type. Note
    // that this new view display mode doesn't contain the comment body.
    EntityViewDisplay::create([
      'targetEntityType' => 'comment',
      'bundle' => 'comment',
      'mode' => $mode,
    ])->setStatus(TRUE)->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $node_display */
    $node_display = EntityViewDisplay::load('node.article.default');
    $formatter = $node_display->getComponent('comment');
    // Change the node comment field formatter to use $mode mode instead of
    // 'default' mode.
    $formatter['settings']['view_mode'] = $mode;
    $node_display
      ->setComponent('comment', $formatter)
      ->save();

    // Reloading the node page to show the same node with its same comment but
    // with a different display mode.
    $this->drupalGet($this->node->toUrl());
    // The comment should exist but without the body text because we used $mode
    // mode this time.
    $comment_element = $this->cssSelect('.comment-wrapper');
    $this->assertNotEmpty($comment_element);
    $this->assertSession()->responseNotContains('<p>' . $comment_text . '</p>');
  }

}
