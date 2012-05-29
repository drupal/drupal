<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentInterfaceTest.
 */

namespace Drupal\comment\Tests;

class CommentInterfaceTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment interface',
      'description' => 'Test comment user interfaces.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests the comment interface.
   */
  function testCommentInterface() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    // Set comments to have subject and preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(FALSE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, t('Comment paging changed.'));
    $this->drupalLogout();

    // Post comment #1 without subject or preview.
    $this->drupalLogin($this->web_user);
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text);
    $comment_loaded = comment_load($comment->id);
    $this->assertTrue($this->commentExists($comment), t('Comment found.'));

    // Set comments to have subject and preview to required.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_REQUIRED);
    $this->drupalLogout();

    // Create comment #2 that allows subject and requires preview.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomName();
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    $comment_loaded = comment_load($comment->id);
    $this->assertTrue($this->commentExists($comment), t('Comment found.'));

    // Check comment display.
    $this->drupalGet('node/' . $this->node->nid . '/' . $comment->id);
    $this->assertText($subject_text, t('Individual comment subject found.'));
    $this->assertText($comment_text, t('Individual comment body found.'));

    // Set comments to have subject and preview to optional.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_OPTIONAL);

    // Test changing the comment author to "Anonymous".
    $this->drupalGet('comment/' . $comment->id . '/edit');
    $comment = $this->postComment(NULL, $comment->comment, $comment->subject, array('name' => ''));
    $comment_loaded = comment_load($comment->id);
    $this->assertTrue(empty($comment_loaded->name) && $comment_loaded->uid == 0, t('Comment author successfully changed to anonymous.'));

    // Test changing the comment author to an unverified user.
    $random_name = $this->randomName();
    $this->drupalGet('comment/' . $comment->id . '/edit');
    $comment = $this->postComment(NULL, $comment->comment, $comment->subject, array('name' => $random_name));
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertText($random_name . ' (' . t('not verified') . ')', t('Comment author successfully changed to an unverified user.'));

    // Test changing the comment author to a verified user.
    $this->drupalGet('comment/' . $comment->id . '/edit');
    $comment = $this->postComment(NULL, $comment->comment, $comment->subject, array('name' => $this->web_user->name));
    $comment_loaded = comment_load($comment->id);
    $this->assertTrue($comment_loaded->name == $this->web_user->name && $comment_loaded->uid == $this->web_user->uid, t('Comment author successfully changed to a registered user.'));

    $this->drupalLogout();

    // Reply to comment #2 creating comment #3 with optional preview and no
    // subject though field enabled.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $comment->id);
    $this->assertText($subject_text, t('Individual comment-reply subject found.'));
    $this->assertText($comment_text, t('Individual comment-reply body found.'));
    $reply = $this->postComment(NULL, $this->randomName(), '', TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($reply, TRUE), t('Reply found.'));
    $this->assertEqual($comment->id, $reply_loaded->pid, t('Pid of a reply to a comment is set correctly.'));
    $this->assertEqual(rtrim($comment_loaded->thread, '/') . '.00/', $reply_loaded->thread, t('Thread of reply grows correctly.'));

    // Second reply to comment #3 creating comment #4.
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $comment->id);
    $this->assertText($subject_text, t('Individual comment-reply subject found.'));
    $this->assertText($comment_text, t('Individual comment-reply body found.'));
    $reply = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($reply, TRUE), t('Second reply found.'));
    $this->assertEqual(rtrim($comment_loaded->thread, '/') . '.01/', $reply_loaded->thread, t('Thread of second reply grows correctly.'));

    // Edit reply.
    $this->drupalGet('comment/' . $reply->id . '/edit');
    $reply = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $this->assertTrue($this->commentExists($reply, TRUE), t('Modified reply found.'));

    // Correct link count
    $this->drupalGet('node');
    $this->assertRaw('4 comments', t('Link to the 4 comments exist.'));

    // Confirm a new comment is posted to the correct page.
    $this->setCommentsPerPage(2);
    $comment_new_page = $this->postComment($this->node, $this->randomName(), $this->randomName(), TRUE);
    $this->assertTrue($this->commentExists($comment_new_page), t('Page one exists. %s'));
    $this->drupalGet('node/' . $this->node->nid, array('query' => array('page' => 1)));
    $this->assertTrue($this->commentExists($reply, TRUE), t('Page two exists. %s'));
    $this->setCommentsPerPage(50);

    // Attempt to reply to an unpublished comment.
    $reply_loaded->status = COMMENT_NOT_PUBLISHED;
    $reply_loaded->save();
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $reply_loaded->cid);
    $this->assertText(t('The comment you are replying to does not exist.'), 'Replying to an unpublished comment');

    // Attempt to post to node with comments disabled.
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => COMMENT_NODE_HIDDEN));
    $this->assertTrue($this->node, t('Article node created.'));
    $this->drupalGet('comment/reply/' . $this->node->nid);
    $this->assertText('This discussion is closed', t('Posting to node with comments disabled'));
    $this->assertNoField('edit-comment', t('Comment body field found.'));

    // Attempt to post to node with read-only comments.
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => COMMENT_NODE_CLOSED));
    $this->assertTrue($this->node, t('Article node created.'));
    $this->drupalGet('comment/reply/' . $this->node->nid);
    $this->assertText('This discussion is closed', t('Posting to node with comments read-only'));
    $this->assertNoField('edit-comment', t('Comment body field found.'));

    // Attempt to post to node with comments enabled (check field names etc).
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => COMMENT_NODE_OPEN));
    $this->assertTrue($this->node, t('Article node created.'));
    $this->drupalGet('comment/reply/' . $this->node->nid);
    $this->assertNoText('This discussion is closed', t('Posting to node with comments enabled'));
    $this->assertField('edit-comment-body-' . $langcode . '-0-value', t('Comment body field found.'));

    // Delete comment and make sure that reply is also removed.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->deleteComment($comment);
    $this->deleteComment($comment_new_page);

    $this->drupalGet('node/' . $this->node->nid);
    $this->assertFalse($this->commentExists($comment), t('Comment not found.'));
    $this->assertFalse($this->commentExists($reply, TRUE), t('Reply not found.'));

    // Enabled comment form on node page.
    $this->drupalLogin($this->admin_user);
    $this->setCommentForm(TRUE);
    $this->drupalLogout();

    // Submit comment through node form.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('node/' . $this->node->nid);
    $form_comment = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $this->assertTrue($this->commentExists($form_comment), t('Form comment found.'));

    // Disable comment form on node page.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->setCommentForm(FALSE);
  }

  /**
   * Tests new comment marker.
   */
  public function testCommentNewCommentsIndicator() {
    // Test if the right links are displayed when no comment is present for the
    // node.
    $this->drupalLogin($this->admin_user);
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => COMMENT_NODE_OPEN));
    $this->drupalGet('node');
    $this->assertNoLink(t('@count comments', array('@count' => 0)));
    $this->assertNoLink(t('@count new comments', array('@count' => 0)));
    $this->assertLink(t('Read more'));
    $count = $this->xpath('//div[@id=:id]/div[@class=:class]/ul/li', array(':id' => 'node-' . $this->node->nid, ':class' => 'link-wrapper'));
    $this->assertTrue(count($count) == 1, t('One child found'));

    // Create a new comment. This helper function may be run with different
    // comment settings so use comment_save() to avoid complex setup.
    $comment = entity_create('comment', array(
      'cid' => NULL,
      'nid' => $this->node->nid,
      'node_type' => $this->node->type,
      'pid' => 0,
      'uid' => $this->loggedInUser->uid,
      'status' => COMMENT_PUBLISHED,
      'subject' => $this->randomName(),
      'hostname' => ip_address(),
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'comment_body' => array(LANGUAGE_NOT_SPECIFIED => array($this->randomName())),
    ));
    comment_save($comment);
    $this->drupalLogout();

    // Log in with 'web user' and check comment links.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('node');
    $this->assertLink(t('1 new comment'));
    $this->clickLink(t('1 new comment'));
    $this->assertRaw('<a id="new"></a>', t('Found "new" marker.'));
    $this->assertTrue($this->xpath('//a[@id=:new]/following-sibling::a[1][@id=:comment_id]', array(':new' => 'new', ':comment_id' => 'comment-1')), t('The "new" anchor is positioned at the right comment.'));

    // Test if "new comment" link is correctly removed.
    $this->drupalGet('node');
    $this->assertLink(t('1 comment'));
    $this->assertLink(t('Read more'));
    $this->assertNoLink(t('1 new comment'));
    $this->assertNoLink(t('@count new comments', array('@count' => 0)));
    $count = $this->xpath('//div[@id=:id]/div[@class=:class]/ul/li', array(':id' => 'node-' . $this->node->nid, ':class' => 'link-wrapper'));
    $this->assertTrue(count($count) == 2, print_r($count, TRUE));
  }

  /**
   * Tests CSS classes on comments.
   */
  function testCommentClasses() {
    // Create all permutations for comments, users, and nodes.
    $parameters = array(
      'node_uid' => array(0, $this->web_user->uid),
      'comment_uid' => array(0, $this->web_user->uid, $this->admin_user->uid),
      'comment_status' => array(COMMENT_PUBLISHED, COMMENT_NOT_PUBLISHED),
      'user' => array('anonymous', 'authenticated', 'admin'),
    );
    $permutations = $this->generatePermutations($parameters);

    foreach ($permutations as $case) {
      // Create a new node.
      $node = $this->drupalCreateNode(array('type' => 'article', 'uid' => $case['node_uid']));

      // Add a comment.
      $comment = entity_create('comment', array(
        'nid' => $node->nid,
        'uid' => $case['comment_uid'],
        'status' => $case['comment_status'],
        'subject' => $this->randomName(),
        'language' => LANGUAGE_NOT_SPECIFIED,
        'comment_body' => array(LANGUAGE_NOT_SPECIFIED => array($this->randomName())),
      ));
      comment_save($comment);

      // Adjust the current/viewing user.
      switch ($case['user']) {
        case 'anonymous':
          if ($this->loggedInUser) {
            $this->drupalLogout();
          }
          $case['user_uid'] = 0;
          break;

        case 'authenticated':
          $this->drupalLogin($this->web_user);
          $case['user_uid'] = $this->web_user->uid;
          break;

        case 'admin':
          $this->drupalLogin($this->admin_user);
          $case['user_uid'] = $this->admin_user->uid;
          break;
      }
      // Request the node with the comment.
      $this->drupalGet('node/' . $node->nid);

      // Verify classes if the comment is visible for the current user.
      if ($case['comment_status'] == COMMENT_PUBLISHED || $case['user'] == 'admin') {
        // Verify the by-anonymous class.
        $comments = $this->xpath('//*[contains(@class, "comment") and contains(@class, "by-anonymous")]');
        if ($case['comment_uid'] == 0) {
          $this->assertTrue(count($comments) == 1, 'by-anonymous class found.');
        }
        else {
          $this->assertFalse(count($comments), 'by-anonymous class not found.');
        }

        // Verify the by-node-author class.
        $comments = $this->xpath('//*[contains(@class, "comment") and contains(@class, "by-node-author")]');
        if ($case['comment_uid'] > 0 && $case['comment_uid'] == $case['node_uid']) {
          $this->assertTrue(count($comments) == 1, 'by-node-author class found.');
        }
        else {
          $this->assertFalse(count($comments), 'by-node-author class not found.');
        }

        // Verify the by-viewer class.
        $comments = $this->xpath('//*[contains(@class, "comment") and contains(@class, "by-viewer")]');
        if ($case['comment_uid'] > 0 && $case['comment_uid'] == $case['user_uid']) {
          $this->assertTrue(count($comments) == 1, 'by-viewer class found.');
        }
        else {
          $this->assertFalse(count($comments), 'by-viewer class not found.');
        }
      }

      // Verify the unpublished class.
      $comments = $this->xpath('//*[contains(@class, "comment") and contains(@class, "unpublished")]');
      if ($case['comment_status'] == COMMENT_NOT_PUBLISHED && $case['user'] == 'admin') {
        $this->assertTrue(count($comments) == 1, 'unpublished class found.');
      }
      else {
        $this->assertFalse(count($comments), 'unpublished class not found.');
      }

      // Verify the new class.
      if ($case['comment_status'] == COMMENT_PUBLISHED || $case['user'] == 'admin') {
        $comments = $this->xpath('//*[contains(@class, "comment") and contains(@class, "new")]');
        if ($case['user'] != 'anonymous') {
          $this->assertTrue(count($comments) == 1, 'new class found.');

          // Request the node again. The new class should disappear.
          $this->drupalGet('node/' . $node->nid);
          $comments = $this->xpath('//*[contains(@class, "comment") and contains(@class, "new")]');
          $this->assertFalse(count($comments), 'new class not found.');
        }
        else {
          $this->assertFalse(count($comments), 'new class not found.');
        }
      }
    }
  }

  /**
   * Tests the node comment statistics.
   */
  function testCommentNodeCommentStatistics() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    // Set comments to have subject and preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(FALSE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, t('Comment paging changed.'));
    $this->drupalLogout();

    // Creates a second user to post comments.
    $this->web_user2 = $this->drupalCreateUser(array('access comments', 'post comments', 'create article content', 'edit own comments'));

    // Checks the initial values of node comment statistics with no comment.
    $node = node_load($this->node->nid);
    $this->assertEqual($node->last_comment_timestamp, $this->node->created, t('The initial value of node last_comment_timestamp is the node created date.'));
    $this->assertEqual($node->last_comment_name, NULL, t('The initial value of node last_comment_name is NULL.'));
    $this->assertEqual($node->last_comment_uid, $this->web_user->uid, t('The initial value of node last_comment_uid is the node uid.'));
    $this->assertEqual($node->comment_count, 0, t('The initial value of node comment_count is zero.'));

    // Post comment #1 as web_user2.
    $this->drupalLogin($this->web_user2);
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text);
    $comment_loaded = comment_load($comment->id);

    // Checks the new values of node comment statistics with comment #1.
    // The node needs to be reloaded with a node_load_multiple cache reset.
    $node = node_load($this->node->nid, NULL, TRUE);
    $this->assertEqual($node->last_comment_name, NULL, t('The value of node last_comment_name is NULL.'));
    $this->assertEqual($node->last_comment_uid, $this->web_user2->uid, t('The value of node last_comment_uid is the comment #1 uid.'));
    $this->assertEqual($node->comment_count, 1, t('The value of node comment_count is 1.'));

    // Prepare for anonymous comment submission (comment approval enabled).
    variable_set('user_register', USER_REGISTER_VISITORS);
    $this->drupalLogin($this->admin_user);
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => FALSE,
    ));
    // Ensure that the poster can leave some contact info.
    $this->setCommentAnonymous('1');
    $this->drupalLogout();

    // Post comment #2 as anonymous (comment approval enabled).
    $this->drupalGet('comment/reply/' . $this->node->nid);
    $anonymous_comment = $this->postComment($this->node, $this->randomName(), '', TRUE);
    $comment_unpublished_loaded = comment_load($anonymous_comment->id);

    // Checks the new values of node comment statistics with comment #2 and
    // ensure they haven't changed since the comment has not been moderated.
    // The node needs to be reloaded with a node_load_multiple cache reset.
    $node = node_load($this->node->nid, NULL, TRUE);
    $this->assertEqual($node->last_comment_name, NULL, t('The value of node last_comment_name is still NULL.'));
    $this->assertEqual($node->last_comment_uid, $this->web_user2->uid, t('The value of node last_comment_uid is still the comment #1 uid.'));
    $this->assertEqual($node->comment_count, 1, t('The value of node comment_count is still 1.'));

    // Prepare for anonymous comment submission (no approval required).
    $this->drupalLogin($this->admin_user);
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ));
    $this->drupalLogout();

    // Post comment #3 as anonymous.
    $this->drupalGet('comment/reply/' . $this->node->nid);
    $anonymous_comment = $this->postComment($this->node, $this->randomName(), '', array('name' => $this->randomName()));
    $comment_loaded = comment_load($anonymous_comment->id);

    // Checks the new values of node comment statistics with comment #3.
    // The node needs to be reloaded with a node_load_multiple cache reset.
    $node = node_load($this->node->nid, NULL, TRUE);
    $this->assertEqual($node->last_comment_name, $comment_loaded->name, t('The value of node last_comment_name is the name of the anonymous user.'));
    $this->assertEqual($node->last_comment_uid, 0, t('The value of node last_comment_uid is zero.'));
    $this->assertEqual($node->comment_count, 2, t('The value of node comment_count is 2.'));
  }

  /**
   * Tests comment links.
   *
   * The output of comment links depends on various environment conditions:
   * - Various Comment module configuration settings, user registration
   *   settings, and user access permissions.
   * - Whether the user is authenticated or not, and whether any comments exist.
   *
   * To account for all possible cases, this test creates permutations of all
   * possible conditions and tests the expected appearance of comment links in
   * each environment.
   */
  function testCommentLinks() {
    // Bartik theme alters comment links, so use a different theme.
    theme_enable(array('stark'));
    variable_set('theme_default', 'stark');

    // Remove additional user permissions from $this->web_user added by setUp(),
    // since this test is limited to anonymous and authenticated roles only.
    user_role_delete(key($this->web_user->roles));

    // Matrix of possible environmental conditions and configuration settings.
    // See setEnvironment() for details.
    $conditions = array(
      'authenticated'   => array(FALSE, TRUE),
      'comment count'   => array(FALSE, TRUE),
      'access comments' => array(0, 1),
      'post comments'   => array(0, 1),
      'form'            => array(COMMENT_FORM_BELOW, COMMENT_FORM_SEPARATE_PAGE),
      // USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL is irrelevant for this
      // test; there is only a difference between open and closed registration.
      'user_register'   => array(USER_REGISTER_VISITORS, USER_REGISTER_ADMINISTRATORS_ONLY),
      // @todo Complete test coverage for:
      //'comments'        => array(COMMENT_NODE_OPEN, COMMENT_NODE_CLOSED, COMMENT_NODE_HIDDEN),
      //// COMMENT_ANONYMOUS_MUST_CONTACT is irrelevant for this test.
      //'contact '        => array(COMMENT_ANONYMOUS_MAY_CONTACT, COMMENT_ANONYMOUS_MAYNOT_CONTACT),
    );

    $environments = $this->generatePermutations($conditions);
    foreach ($environments as $info) {
      $this->assertCommentLinks($info);
    }
  }

  /**
   * Re-configures the environment, module settings, and user permissions.
   *
   * @param $info
   *   An associative array describing the environment to setup:
   *   - Environment conditions:
   *     - authenticated: Boolean whether to test with $this->web_user or
   *       anonymous.
   *     - comment count: Boolean whether to test with a new/unread comment on
   *       $this->node or no comments.
   *   - Configuration settings:
   *     - form: COMMENT_FORM_BELOW or COMMENT_FORM_SEPARATE_PAGE.
   *     - user_register: USER_REGISTER_ADMINISTRATORS_ONLY or
   *       USER_REGISTER_VISITORS.
   *     - contact: COMMENT_ANONYMOUS_MAY_CONTACT or
   *       COMMENT_ANONYMOUS_MAYNOT_CONTACT.
   *     - comments: COMMENT_NODE_OPEN, COMMENT_NODE_CLOSED, or
   *       COMMENT_NODE_HIDDEN.
   *   - User permissions:
   *     These are granted or revoked for the user, according to the
   *     'authenticated' flag above. Pass 0 or 1 as parameter values. See
   *     user_role_change_permissions().
   *     - access comments
   *     - post comments
   *     - skip comment approval
   *     - edit own comments
   */
  function setEnvironment(array $info) {
    static $current;

    // Apply defaults to initial environment.
    if (!isset($current)) {
      $current = array(
        'authenticated' => FALSE,
        'comment count' => FALSE,
        'form' => COMMENT_FORM_BELOW,
        'user_register' => USER_REGISTER_VISITORS,
        'contact' => COMMENT_ANONYMOUS_MAY_CONTACT,
        'comments' => COMMENT_NODE_OPEN,
        'access comments' => 0,
        'post comments' => 0,
        // Enabled by default, because it's irrelevant for this test.
        'skip comment approval' => 1,
        'edit own comments' => 0,
      );
    }
    // Complete new environment with current environment.
    $info = array_merge($current, $info);

    // Change environment conditions.
    if ($current['authenticated'] != $info['authenticated']) {
      if ($this->loggedInUser) {
        $this->drupalLogout();
      }
      else {
        $this->drupalLogin($this->web_user);
      }
    }
    if ($current['comment count'] != $info['comment count']) {
      if ($info['comment count']) {
        // Create a comment via CRUD API functionality, since
        // $this->postComment() relies on actual user permissions.
        $comment = entity_create('comment', array(
          'cid' => NULL,
          'nid' => $this->node->nid,
          'node_type' => $this->node->type,
          'pid' => 0,
          'uid' => 0,
          'status' => COMMENT_PUBLISHED,
          'subject' => $this->randomName(),
          'hostname' => ip_address(),
          'langcode' => LANGUAGE_NOT_SPECIFIED,
          'comment_body' => array(LANGUAGE_NOT_SPECIFIED => array($this->randomName())),
        ));
        comment_save($comment);
        $this->comment = $comment;

        // comment_num_new() relies on node_last_viewed(), so ensure that no one
        // has seen the node of this comment.
        db_delete('history')->condition('nid', $this->node->nid)->execute();
      }
      else {
        $cids = db_query("SELECT cid FROM {comment}")->fetchCol();
        comment_delete_multiple($cids);
        unset($this->comment);
      }
    }

    // Change comment settings.
    variable_set('comment_form_location_' . $this->node->type, $info['form']);
    variable_set('comment_anonymous_' . $this->node->type, $info['contact']);
    if ($this->node->comment != $info['comments']) {
      $this->node->comment = $info['comments'];
      node_save($this->node);
    }

    // Change user settings.
    variable_set('user_register', $info['user_register']);

    // Change user permissions.
    $rid = ($this->loggedInUser ? DRUPAL_AUTHENTICATED_RID : DRUPAL_ANONYMOUS_RID);
    $perms = array_intersect_key($info, array('access comments' => 1, 'post comments' => 1, 'skip comment approval' => 1, 'edit own comments' => 1));
    user_role_change_permissions($rid, $perms);

    // Output verbose debugging information.
    // @see Drupal\simpletest\TestBase::error()
    $t_form = array(
      COMMENT_FORM_BELOW => 'below',
      COMMENT_FORM_SEPARATE_PAGE => 'separate page',
    );
    $t_contact = array(
      COMMENT_ANONYMOUS_MAY_CONTACT => 'optional',
      COMMENT_ANONYMOUS_MAYNOT_CONTACT => 'disabled',
      COMMENT_ANONYMOUS_MUST_CONTACT => 'required',
    );
    $t_comments = array(
      COMMENT_NODE_OPEN => 'open',
      COMMENT_NODE_CLOSED => 'closed',
      COMMENT_NODE_HIDDEN => 'hidden',
    );
    $verbose = $info;
    $verbose['form'] = $t_form[$info['form']];
    $verbose['contact'] = $t_contact[$info['contact']];
    $verbose['comments'] = $t_comments[$info['comments']];
    $message = t('Changed environment:<pre>@verbose</pre>', array(
      '@verbose' => var_export($verbose, TRUE),
    ));
    $this->assert('debug', $message, 'Debug');

    // Update current environment.
    $current = $info;

    return $info;
  }

  /**
   * Asserts that comment links appear according to the passed environment.
   *
   * @param $info
   *   An associative array describing the environment to pass to
   *   setEnvironment().
   */
  function assertCommentLinks(array $info) {
    $info = $this->setEnvironment($info);

    $nid = $this->node->nid;

    foreach (array('', "node/$nid") as $path) {
      $this->drupalGet($path);

      // User is allowed to view comments.
      if ($info['access comments']) {
        if ($path == '') {
          // In teaser view, a link containing the comment count is always
          // expected.
          if ($info['comment count']) {
            $this->assertLink(t('1 comment'));

            // For logged in users, a link containing the amount of new/unread
            // comments is expected.
            // See important note about comment_num_new() below.
            if ($this->loggedInUser && isset($this->comment) && !isset($this->comment->seen)) {
              $this->assertLink(t('1 new comment'));
              $this->comment->seen = TRUE;
            }
          }
        }
      }
      else {
        $this->assertNoLink(t('1 comment'));
        $this->assertNoLink(t('1 new comment'));
      }
      // comment_num_new() is based on node views, so comments are marked as
      // read when a node is viewed, regardless of whether we have access to
      // comments.
      if ($path == "node/$nid" && $this->loggedInUser && isset($this->comment)) {
        $this->comment->seen = TRUE;
      }

      // User is not allowed to post comments.
      if (!$info['post comments']) {
        $this->assertNoLink('Add new comment');

        // Anonymous users should see a note to log in or register in case
        // authenticated users are allowed to post comments.
        // @see theme_comment_post_forbidden()
        if (!$this->loggedInUser) {
          if (user_access('post comments', $this->web_user)) {
            // The note depends on whether users are actually able to register.
            if ($info['user_register']) {
              $this->assertText('Log in or register to post comments');
            }
            else {
              $this->assertText('Log in to post comments');
            }
          }
          else {
            $this->assertNoText('Log in or register to post comments');
            $this->assertNoText('Log in to post comments');
          }
        }
      }
      // User is allowed to post comments.
      else {
        $this->assertNoText('Log in or register to post comments');

        // "Add new comment" is always expected, except when there are no
        // comments or if the user cannot see them.
        if ($path == "node/$nid" && $info['form'] == COMMENT_FORM_BELOW && (!$info['comment count'] || !$info['access comments'])) {
          $this->assertNoLink('Add new comment');
        }
        else {
          $this->assertLink('Add new comment');

          // Verify that the "Add new comment" link points to the correct URL
          // based on the comment form location configuration.
          if ($info['form'] == COMMENT_FORM_SEPARATE_PAGE) {
            $this->assertLinkByHref("comment/reply/$nid#comment-form", 0, 'Comment form link destination is on a separate page.');
            $this->assertNoLinkByHref("node/$nid#comment-form");
          }
          else {
            $this->assertLinkByHref("node/$nid#comment-form", 0, 'Comment form link destination is on node.');
            $this->assertNoLinkByHref("comment/reply/$nid#comment-form");
          }
        }

        // Also verify that the comment form appears according to the configured
        // location.
        if ($path == "node/$nid") {
          $elements = $this->xpath('//form[@id=:id]', array(':id' => 'comment-form'));
          if ($info['form'] == COMMENT_FORM_BELOW) {
            $this->assertTrue(count($elements), t('Comment form found below.'));
          }
          else {
            $this->assertFalse(count($elements), t('Comment form not found below.'));
          }
        }
      }
    }
  }
}
