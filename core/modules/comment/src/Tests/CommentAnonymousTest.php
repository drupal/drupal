<?php

namespace Drupal\comment\Tests;

use Drupal\user\RoleInterface;

/**
 * Tests anonymous commenting.
 *
 * @group comment
 */
class CommentAnonymousTest extends CommentTestBase {

  protected function setUp() {
    parent::setUp();

    // Enable anonymous and authenticated user comments.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access comments',
      'post comments',
      'skip comment approval',
    ));
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, array(
      'access comments',
      'post comments',
      'skip comment approval',
    ));
  }

  /**
   * Tests anonymous comment functionality.
   */
  function testAnonymous() {
    $this->drupalLogin($this->adminUser);
    $this->setCommentAnonymous(COMMENT_ANONYMOUS_MAYNOT_CONTACT);
    $this->drupalLogout();

    // Preview comments (with `skip comment approval` permission).
    $edit = [];
    $title = 'comment title with skip comment approval';
    $body = 'comment body with skip comment approval';
    $edit['subject[0][value]'] = $title;
    $edit['comment_body[0][value]'] = $body;
    $this->drupalPostForm($this->node->urlInfo(), $edit, t('Preview'));
    // Cannot use assertRaw here since both title and body are in the form.
    $preview = (string) $this->cssSelect('.preview')[0]->asXML();
    $this->assertTrue(strpos($preview, $title) !== FALSE, 'Anonymous user can preview comment title.');
    $this->assertTrue(strpos($preview, $body) !== FALSE, 'Anonymous user can preview comment body.');

    // Preview comments (without `skip comment approval` permission).
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['skip comment approval']);
    $edit = [];
    $title = 'comment title without skip comment approval';
    $body = 'comment body without skip comment approval';
    $edit['subject[0][value]'] = $title;
    $edit['comment_body[0][value]'] = $body;
    $this->drupalPostForm($this->node->urlInfo(), $edit, t('Preview'));
    // Cannot use assertRaw here since both title and body are in the form.
    $preview = (string) $this->cssSelect('.preview')[0]->asXML();
    $this->assertTrue(strpos($preview, $title) !== FALSE, 'Anonymous user can preview comment title.');
    $this->assertTrue(strpos($preview, $body) !== FALSE, 'Anonymous user can preview comment body.');
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['skip comment approval']);

    // Post anonymous comment without contact info.
    $anonymous_comment1 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($anonymous_comment1), 'Anonymous comment without contact info found.');

    // Allow contact info.
    $this->drupalLogin($this->adminUser);
    $this->setCommentAnonymous(COMMENT_ANONYMOUS_MAY_CONTACT);

    // Attempt to edit anonymous comment.
    $this->drupalGet('comment/' . $anonymous_comment1->id() . '/edit');
    $edited_comment = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($edited_comment, FALSE), 'Modified reply found.');
    $this->drupalLogout();

    // Post anonymous comment with contact info (optional).
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertTrue($this->commentContactInfoAvailable(), 'Contact information available.');

    // Check the presence of expected cache tags.
    $this->assertCacheTag('config:field.field.node.article.comment');
    $this->assertCacheTag('config:user.settings');

    $anonymous_comment2 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($this->commentExists($anonymous_comment2), 'Anonymous comment with contact info (optional) found.');

    // Ensure anonymous users cannot post in the name of registered users.
    $edit = array(
      'name' => $this->adminUser->getUsername(),
      'mail' => $this->randomMachineName() . '@example.com',
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(),
    );
    $this->drupalPostForm('comment/reply/node/' . $this->node->id() . '/comment', $edit, t('Save'));
    $this->assertRaw(t('The name you used (%name) belongs to a registered user.', [
      '%name' => $this->adminUser->getUsername(),
    ]));

    // Require contact info.
    $this->drupalLogin($this->adminUser);
    $this->setCommentAnonymous(COMMENT_ANONYMOUS_MUST_CONTACT);
    $this->drupalLogout();

    // Try to post comment with contact info (required).
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertTrue($this->commentContactInfoAvailable(), 'Contact information available.');

    $anonymous_comment3 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    // Name should have 'Anonymous' for value by default.
    $this->assertText(t('Email field is required.'), 'Email required.');
    $this->assertFalse($this->commentExists($anonymous_comment3), 'Anonymous comment with contact info (required) not found.');

    // Post comment with contact info (required).
    $author_name = $this->randomMachineName();
    $author_mail = $this->randomMachineName() . '@example.com';
    $anonymous_comment3 = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), array('name' => $author_name, 'mail' => $author_mail));
    $this->assertTrue($this->commentExists($anonymous_comment3), 'Anonymous comment with contact info (required) found.');

    // Make sure the user data appears correctly when editing the comment.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('comment/' . $anonymous_comment3->id() . '/edit');
    $this->assertRaw($author_name, "The anonymous user's name is correct when editing the comment.");
    $this->assertFieldByName('uid', '', 'The author field is empty (i.e. anonymous) when editing the comment.');
    $this->assertRaw($author_mail, "The anonymous user's email address is correct when editing the comment.");

    // Unpublish comment.
    $this->performCommentOperation($anonymous_comment3, 'unpublish');

    $this->drupalGet('admin/content/comment/approval');
    $this->assertRaw('comments[' . $anonymous_comment3->id() . ']', 'Comment was unpublished.');

    // Publish comment.
    $this->performCommentOperation($anonymous_comment3, 'publish', TRUE);

    $this->drupalGet('admin/content/comment');
    $this->assertRaw('comments[' . $anonymous_comment3->id() . ']', 'Comment was published.');

    // Delete comment.
    $this->performCommentOperation($anonymous_comment3, 'delete');

    $this->drupalGet('admin/content/comment');
    $this->assertNoRaw('comments[' . $anonymous_comment3->id() . ']', 'Comment was deleted.');
    $this->drupalLogout();

    // Comment 3 was deleted.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $anonymous_comment3->id());
    $this->assertResponse(403);

    // Reset.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access comments' => FALSE,
      'post comments' => FALSE,
      'skip comment approval' => FALSE,
    ));

    // Attempt to view comments while disallowed.
    // NOTE: if authenticated user has permission to post comments, then a
    // "Login or register to post comments" type link may be shown.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoPattern('@<h2[^>]*>Comments</h2>@', 'Comments were not displayed.');
    $this->assertNoLink('Add new comment', 'Link to add comment was found.');

    // Attempt to view node-comment form while disallowed.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertResponse(403);

    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access comments' => TRUE,
      'post comments' => FALSE,
      'skip comment approval' => FALSE,
    ));
    $this->drupalGet('node/' . $this->node->id());
    $this->assertPattern('@<h2[^>]*>Comments</h2>@', 'Comments were displayed.');
    $this->assertLink('Log in', 1, 'Link to login was found.');
    $this->assertLink('register', 1, 'Link to register was found.');

    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access comments' => FALSE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ));
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoPattern('@<h2[^>]*>Comments</h2>@', 'Comments were not displayed.');
    $this->assertFieldByName('subject[0][value]', '', 'Subject field found.');
    $this->assertFieldByName('comment_body[0][value]', '', 'Comment field found.');

    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $anonymous_comment2->id());
    $this->assertResponse(403);
  }

}
