<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentAdminTest.
 */

namespace Drupal\comment\Tests;

use Drupal\user\RoleInterface;

/**
 * Tests comment approval functionality.
 *
 * @group comment
 */
class CommentAdminTest extends CommentTestBase {

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Test comment approval functionality through admin/content/comment.
   */
  function testApprovalAdminInterface() {
    // Set anonymous comments to require approval.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => FALSE,
    ));
    $this->drupalLogin($this->adminUser);
    $this->setCommentAnonymous('0'); // Ensure that doesn't require contact info.

    // Test that the comments page loads correctly when there are no comments
    $this->drupalGet('admin/content/comment');
    $this->assertText(t('No comments available.'));

    $this->drupalLogout();

    // Post anonymous comment without contact info.
    $subject = $this->randomMachineName();
    $body = $this->randomMachineName();
    $this->postComment($this->node, $body, $subject, TRUE); // Set $contact to true so that it won't check for id and message.
    $this->assertText(t('Your comment has been queued for review by site administrators and will be published after approval.'), 'Comment requires approval.');

    // Get unapproved comment id.
    $this->drupalLogin($this->adminUser);
    $anonymous_comment4 = $this->getUnapprovedComment($subject);
    $anonymous_comment4 = entity_create('comment', array(
      'cid' => $anonymous_comment4,
      'subject' => $subject,
      'comment_body' => $body,
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment'
    ));
    $this->drupalLogout();

    $this->assertFalse($this->commentExists($anonymous_comment4), 'Anonymous comment was not published.');

    // Approve comment.
    $this->drupalLogin($this->adminUser);
    $this->performCommentOperation($anonymous_comment4, 'publish', TRUE);
    $this->drupalLogout();

    $this->drupalGet('node/' . $this->node->id());
    $this->assertTrue($this->commentExists($anonymous_comment4), 'Anonymous comment visible.');

    // Post 2 anonymous comments without contact info.
    $comments[] = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $comments[] = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Publish multiple comments in one operation.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content/comment/approval');
    $this->assertText(t('Unapproved comments (@count)', array('@count' => 2)), 'Two unapproved comments waiting for approval.');
    $edit = array(
      "comments[{$comments[0]->id()}]" => 1,
      "comments[{$comments[1]->id()}]" => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Update'));
    $this->assertText(t('Unapproved comments (@count)', array('@count' => 0)), 'All comments were approved.');

    // Delete multiple comments in one operation.
    $edit = array(
      'operation' => 'delete',
      "comments[{$comments[0]->id()}]" => 1,
      "comments[{$comments[1]->id()}]" => 1,
      "comments[{$anonymous_comment4->id()}]" => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Update'));
    $this->assertText(t('Are you sure you want to delete these comments and all their children?'), 'Confirmation required.');
    $this->drupalPostForm(NULL, $edit, t('Delete comments'));
    $this->assertText(t('No comments available.'), 'All comments were deleted.');
    // Test message when no comments selected.
    $edit = array(
      'operation' => 'delete',
    );
    $this->drupalPostForm(NULL, $edit, t('Update'));
    $this->assertText(t('Select one or more comments to perform the update on.'));
  }

  /**
   * Tests comment approval functionality through the node interface.
   */
  function testApprovalNodeInterface() {
    // Set anonymous comments to require approval.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => FALSE,
    ));
    $this->drupalLogin($this->adminUser);
    $this->setCommentAnonymous('0'); // Ensure that doesn't require contact info.
    $this->drupalLogout();

    // Post anonymous comment without contact info.
    $subject = $this->randomMachineName();
    $body = $this->randomMachineName();
    $this->postComment($this->node, $body, $subject, TRUE); // Set $contact to true so that it won't check for id and message.
    $this->assertText(t('Your comment has been queued for review by site administrators and will be published after approval.'), 'Comment requires approval.');

    // Get unapproved comment id.
    $this->drupalLogin($this->adminUser);
    $anonymous_comment4 = $this->getUnapprovedComment($subject);
    $anonymous_comment4 = entity_create('comment', array(
      'cid' => $anonymous_comment4,
      'subject' => $subject,
      'comment_body' => $body,
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment'
    ));
    $this->drupalLogout();

    $this->assertFalse($this->commentExists($anonymous_comment4), 'Anonymous comment was not published.');

    // Approve comment.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('comment/1/approve');
    $this->assertResponse(403, 'Forged comment approval was denied.');
    $this->drupalGet('comment/1/approve', array('query' => array('token' => 'forged')));
    $this->assertResponse(403, 'Forged comment approval was denied.');
    $this->drupalGet('comment/1/edit');
    $this->assertFieldChecked('edit-status-0');
    $this->drupalGet('node/' . $this->node->id());
    $this->clickLink(t('Approve'));
    $this->drupalLogout();

    $this->drupalGet('node/' . $this->node->id());
    $this->assertTrue($this->commentExists($anonymous_comment4), 'Anonymous comment visible.');
  }

  /**
   * Tests comment bundle admin.
   */
  public function testCommentAdmin() {
    // Login.
    $this->drupalLogin($this->adminUser);
    // Browse to comment bundle overview.
    $this->drupalGet('admin/structure/comment');
    $this->assertResponse(200);
    // Make sure titles visible.
    $this->assertText('Comment type');
    $this->assertText('Description');
    // Make sure the description is present.
    $this->assertText('Default comment field');
    // Manage fields.
    $this->clickLink('Manage fields');
    $this->assertResponse(200);
    // Make sure comment_body field is shown.
    $this->assertText('comment_body');
    // Rest from here on in is field_ui.
  }

  /**
   * Tests editing a comment as an admin.
   */
  public function testEditComment() {
    // Enable anonymous user comments.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access comments',
      'post comments',
      'skip comment approval',
    ));

    // Login as a web user.
    $this->drupalLogin($this->webUser);
    // Post a comment.
    $comment = $this->postComment($this->node, $this->randomMachineName());

    $this->drupalLogout();

    // Post anonymous comment.
    $this->drupalLogin($this->adminUser);
    $this->setCommentAnonymous('2'); // Ensure that we need email id before posting comment.
    $this->drupalLogout();

    // Post comment with contact info (required).
    $author_name = $this->randomMachineName();
    $author_mail = $this->randomMachineName() . '@example.com';
    $anonymous_comment = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), array('name' => $author_name, 'mail' => $author_mail));

    // Login as an admin user.
    $this->drupalLogin($this->adminUser);

    // Make sure the comment field is not visible when
    // the comment was posted by an authenticated user.
    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $this->assertNoFieldById('edit-mail', $comment->getAuthorEmail());

    // Make sure the comment field is visible when
    // the comment was posted by an anonymous user.
    $this->drupalGet('comment/' . $anonymous_comment->id() . '/edit');
    $this->assertFieldById('edit-mail', $anonymous_comment->getAuthorEmail());
  }
}
