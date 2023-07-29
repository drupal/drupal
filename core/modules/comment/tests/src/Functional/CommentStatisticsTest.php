<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Entity\Comment;

/**
 * Tests comment statistics on nodes.
 *
 * @group comment
 */
class CommentStatisticsTest extends CommentTestBase {

  /**
   * A secondary user for posting comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser2;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a second user to post comments.
    $this->webUser2 = $this->drupalCreateUser([
      'post comments',
      'create article content',
      'edit own comments',
      'post comments',
      'skip comment approval',
      'access comments',
      'access content',
    ]);
  }

  /**
   * Tests the node comment statistics.
   */
  public function testCommentNodeCommentStatistics() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    // Set comments to have subject and preview disabled.
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(FALSE);
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');

    // Checks the initial values of node comment statistics with no comment.
    $node = $node_storage->load($this->node->id());
    $this->assertEquals($this->node->getCreatedTime(), $node->get('comment')->last_comment_timestamp, 'The initial value of node last_comment_timestamp is the node created date.');
    $this->assertNull($node->get('comment')->last_comment_name, 'The initial value of node last_comment_name is NULL.');
    $this->assertEquals($this->webUser->id(), $node->get('comment')->last_comment_uid, 'The initial value of node last_comment_uid is the node uid.');
    $this->assertEquals(0, $node->get('comment')->comment_count, 'The initial value of node comment_count is zero.');

    // Post comment #1 as web_user2.
    $this->drupalLogin($this->webUser2);
    $comment_text = $this->randomMachineName();
    $this->postComment($this->node, $comment_text);

    // Checks the new values of node comment statistics with comment #1.
    // The node cache needs to be reset before reload.
    $node_storage->resetCache([$this->node->id()]);
    $node = $node_storage->load($this->node->id());
    $this->assertSame('', $node->get('comment')->last_comment_name, 'The value of node last_comment_name should be an empty string.');
    $this->assertEquals($this->webUser2->id(), $node->get('comment')->last_comment_uid, 'The value of node last_comment_uid is the comment #1 uid.');
    $this->assertEquals(1, $node->get('comment')->comment_count, 'The value of node comment_count is 1.');
    $this->drupalLogout();

    // Prepare for anonymous comment submission (comment approval enabled).
    // Note we don't use user_role_change_permissions(), because that caused
    // random test failures.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/people/permissions');
    $edit = [
      'anonymous[access comments]' => 1,
      'anonymous[post comments]' => 1,
      'anonymous[skip comment approval]' => 0,
    ];
    $this->submitForm($edit, 'Save permissions');
    $this->drupalLogout();

    // Ensure that the poster can leave some contact info.
    $this->setCommentAnonymous(CommentInterface::ANONYMOUS_MAY_CONTACT);

    // Post comment #2 as anonymous (comment approval enabled).
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $anonymous_comment = $this->postComment($this->node, $this->randomMachineName(), '', TRUE);

    // Checks the new values of node comment statistics with comment #2 and
    // ensure they haven't changed since the comment has not been moderated.
    // The node needs to be reloaded with the cache reset.
    $node_storage->resetCache([$this->node->id()]);
    $node = $node_storage->load($this->node->id());
    $this->assertSame('', $node->get('comment')->last_comment_name, 'The value of node last_comment_name should be an empty string.');
    $this->assertEquals($this->webUser2->id(), $node->get('comment')->last_comment_uid, 'The value of node last_comment_uid is still the comment #1 uid.');
    $this->assertEquals(1, $node->get('comment')->comment_count, 'The value of node comment_count is still 1.');

    // Prepare for anonymous comment submission (no approval required).
    // Note we don't use user_role_change_permissions(), because that caused
    // random test failures.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/people/permissions');
    $edit = [
      'anonymous[skip comment approval]' => 1,
    ];
    $this->submitForm($edit, 'Save permissions');
    $this->drupalLogout();

    // Post comment #3 as anonymous.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $anonymous_comment = $this->postComment($this->node, $this->randomMachineName(), '', ['name' => $this->randomMachineName()]);
    $comment_loaded = Comment::load($anonymous_comment->id());

    // Checks the new values of node comment statistics with comment #3.
    // The node needs to be reloaded with the cache reset.
    $node_storage->resetCache([$this->node->id()]);
    $node = $node_storage->load($this->node->id());
    $this->assertEquals($comment_loaded->getAuthorName(), $node->get('comment')->last_comment_name, 'The value of node last_comment_name is the name of the anonymous user.');
    $this->assertEquals(0, $node->get('comment')->last_comment_uid, 'The value of node last_comment_uid is zero.');
    $this->assertEquals(2, $node->get('comment')->comment_count, 'The value of node comment_count is 2.');
  }

}
