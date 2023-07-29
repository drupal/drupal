<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentInterface;
use Drupal\user\RoleInterface;

/**
 * Tests anonymous commenting.
 *
 * @group comment
 */
class CommentAnonymousTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable anonymous and authenticated user comments.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments',
      'post comments',
      'skip comment approval',
    ]);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, [
      'access comments',
      'post comments',
      'skip comment approval',
    ]);
  }

  /**
   * Tests anonymous comment functionality.
   */
  public function testAnonymous() {
    $this->setCommentAnonymous(CommentInterface::ANONYMOUS_MAYNOT_CONTACT);

    // Preview comments (with `skip comment approval` permission).
    $edit = [];
    $title = 'comment title with skip comment approval';
    $body = 'comment body with skip comment approval';
    $edit['subject[0][value]'] = $title;
    $edit['comment_body[0][value]'] = $body;
    $this->drupalGet($this->node->toUrl());
    $this->submitForm($edit, 'Preview');
    // Cannot use assertRaw here since both title and body are in the form.
    $preview = (string) $this->cssSelect('[data-drupal-selector="edit-comment-preview"]')[0]->getHtml();
    $this->assertStringContainsString($title, $preview, 'Anonymous user can preview comment title.');
    $this->assertStringContainsString($body, $preview, 'Anonymous user can preview comment body.');

    // Preview comments (without `skip comment approval` permission).
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['skip comment approval']);
    $edit = [];
    $title = 'comment title without skip comment approval';
    $body = 'comment body without skip comment approval';
    $edit['subject[0][value]'] = $title;
    $edit['comment_body[0][value]'] = $body;
    $this->drupalGet($this->node->toUrl());
    $this->submitForm($edit, 'Preview');
    // Cannot use assertRaw here since both title and body are in the form.
    $preview = (string) $this->cssSelect('[data-drupal-selector="edit-comment-preview"]')[0]->getHtml();
    $this->assertStringContainsString($title, $preview, 'Anonymous user can preview comment title.');
    $this->assertStringContainsString($body, $preview, 'Anonymous user can preview comment body.');
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['skip comment approval']);

    // Post anonymous comment without contact info.
    $anonymous_comment1 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($anonymous_comment1), 'Anonymous comment without contact info found.');

    // Ensure anonymous users cannot post in the name of registered users.
    $edit = [
      'name' => $this->adminUser->getAccountName(),
      'comment_body[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The name you used (' . $this->adminUser->getAccountName() . ') belongs to a registered user.');

    // Allow contact info.
    $this->drupalLogin($this->adminUser);
    $this->setCommentAnonymous(CommentInterface::ANONYMOUS_MAY_CONTACT);

    // Attempt to edit anonymous comment.
    $this->drupalGet('comment/' . $anonymous_comment1->id() . '/edit');
    $edited_comment = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($edited_comment, FALSE), 'Modified reply found.');
    $this->drupalLogout();

    // Post anonymous comment with contact info (optional).
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertTrue($this->commentContactInfoAvailable(), 'Contact information available.');

    // Check the presence of expected cache tags.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:field.field.node.article.comment');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.settings');

    $anonymous_comment2 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($anonymous_comment2), 'Anonymous comment with contact info (optional) found.');

    // Ensure anonymous users cannot post in the name of registered users.
    $edit = [
      'name' => $this->adminUser->getAccountName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The name you used (' . $this->adminUser->getAccountName() . ') belongs to a registered user.');

    // Require contact info.
    $this->setCommentAnonymous(CommentInterface::ANONYMOUS_MUST_CONTACT);

    // Try to post comment with contact info (required).
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertTrue($this->commentContactInfoAvailable(), 'Contact information available.');

    $anonymous_comment3 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    // Name should have 'Anonymous' for value by default.
    $this->assertSession()->pageTextContains('Email field is required.');
    $this->assertFalse($this->commentExists($anonymous_comment3), 'Anonymous comment with contact info (required) not found.');

    // Post comment with contact info (required).
    $author_name = $this->randomMachineName();
    $author_mail = $this->randomMachineName() . '@example.com';
    $anonymous_comment3 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), ['name' => $author_name, 'mail' => $author_mail]);
    $this->assertTrue($this->commentExists($anonymous_comment3), 'Anonymous comment with contact info (required) found.');

    // Make sure the user data appears correctly when editing the comment.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('comment/' . $anonymous_comment3->id() . '/edit');
    $this->assertSession()->responseContains($author_name);
    // Check the author field is empty (i.e. anonymous) when editing the comment.
    $this->assertSession()->fieldValueEquals('uid', '');
    $this->assertSession()->responseContains($author_mail);

    // Unpublish comment.
    $this->performCommentOperation($anonymous_comment3, 'unpublish');

    $this->drupalGet('admin/content/comment/approval');
    $this->assertSession()->responseContains('comments[' . $anonymous_comment3->id() . ']');

    // Publish comment.
    $this->performCommentOperation($anonymous_comment3, 'publish', TRUE);

    $this->drupalGet('admin/content/comment');
    $this->assertSession()->responseContains('comments[' . $anonymous_comment3->id() . ']');

    // Delete comment.
    $this->performCommentOperation($anonymous_comment3, 'delete');

    $this->drupalGet('admin/content/comment');
    $this->assertSession()->responseNotContains('comments[' . $anonymous_comment3->id() . ']');
    $this->drupalLogout();

    // Comment 3 was deleted.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $anonymous_comment3->id());
    $this->assertSession()->statusCodeEquals(403);

    // Reset.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => FALSE,
      'post comments' => FALSE,
      'skip comment approval' => FALSE,
    ]);

    // Attempt to view comments while disallowed.
    // NOTE: if authenticated user has permission to post comments, then a
    // "Login or register to post comments" type link may be shown.
    $this->drupalGet('node/' . $this->node->id());
    // Verify that comments were not displayed.
    $this->assertSession()->responseNotMatches('@<h2[^>]*>Comments</h2>@');
    $this->assertSession()->linkNotExists('Add new comment', 'Link to add comment was found.');

    // Attempt to view node-comment form while disallowed.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertSession()->statusCodeEquals(403);

    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => TRUE,
      'post comments' => FALSE,
      'skip comment approval' => FALSE,
    ]);
    $this->drupalGet('node/' . $this->node->id());
    // Verify that the comment field title is displayed.
    $this->assertSession()->responseMatches('@<h2[^>]*>Comments</h2>@');
    $this->assertSession()->linkExists('Log in', 1, 'Link to login was found.');
    $this->assertSession()->linkExists('register', 1, 'Link to register was found.');

    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => FALSE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ]);
    $this->drupalGet('node/' . $this->node->id());
    // Verify that comments were not displayed.
    $this->assertSession()->responseNotMatches('@<h2[^>]*>Comments</h2>@');
    $this->assertSession()->fieldValueEquals('subject[0][value]', '');
    $this->assertSession()->fieldValueEquals('comment_body[0][value]', '');

    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $anonymous_comment2->id());
    $this->assertSession()->statusCodeEquals(403);
  }

}
