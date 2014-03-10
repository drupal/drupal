<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentLinksTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Language\Language;
use Drupal\comment\CommentInterface;

/**
 * Tests comment links based on environment configurations.
 */
class CommentLinksTest extends CommentTestBase {

  /**
   * Use the main node listing to test rendering on teasers.
   *
   * @var array
   *
   * @todo Remove this dependency.
   */
  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => 'Comment links',
      'description' => 'Tests comment links based on environment configurations.',
      'group' => 'Comment',
    );
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
    \Drupal::config('system.theme')
      ->set('default', 'stark')
      ->save();

    // Remove additional user permissions from $this->web_user added by setUp(),
    // since this test is limited to anonymous and authenticated roles only.
    $roles = $this->web_user->getRoles();
    entity_delete_multiple('user_role', array(reset($roles)));

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
      //'comments'        => array(CommentItemInterface::OPEN, CommentItemInterface::CLOSED, CommentInterface::_HIDDEN),
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
   *     - comments: CommentItemInterface::OPEN, CommentItemInterface::CLOSED or
   *       CommentItemInterface::HIDDEN.
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
        'comments' => CommentItemInterface::OPEN,
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
          'entity_id' => $this->node->id(),
          'entity_type' => 'node',
          'field_name' => 'comment',
          'pid' => 0,
          'uid' => 0,
          'status' => CommentInterface::PUBLISHED,
          'subject' => $this->randomName(),
          'hostname' => '127.0.0.1',
          'langcode' => Language::LANGCODE_NOT_SPECIFIED,
          'comment_body' => array(Language::LANGCODE_NOT_SPECIFIED => array($this->randomName())),
        ));
        $comment->save();
        $this->comment = $comment;
      }
      else {
        $cids = db_query("SELECT cid FROM {comment}")->fetchCol();
        entity_delete_multiple('comment', $cids);
        unset($this->comment);
      }
    }

    // Change comment settings.
    $this->setCommentSettings('form_location', $info['form'], 'Set comment form location');
    $this->setCommentAnonymous($info['contact']);
    if ($this->node->comment->status != $info['comments']) {
      $this->node->comment = $info['comments'];
      $this->node->save();
    }

    // Change user settings.
    \Drupal::config('user.settings')->set('register', $info['user_register'])->save();

    // Change user permissions.
    $rid = ($this->loggedInUser ? DRUPAL_AUTHENTICATED_RID : DRUPAL_ANONYMOUS_RID);
    $perms = array_intersect_key($info, array('access comments' => 1, 'post comments' => 1, 'skip comment approval' => 1, 'edit own comments' => 1));
    user_role_change_permissions($rid, $perms);

    // Output verbose debugging information.
    // @see \Drupal\simpletest\TestBase::error()
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
      CommentItemInterface::OPEN => 'open',
      CommentItemInterface::CLOSED => 'closed',
      CommentItemInterface::HIDDEN => 'hidden',
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

    $nid = $this->node->id();

    foreach (array('node', "node/$nid") as $path) {
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
        // @see \Drupal\comment\CommentManagerInterface::forbiddenMessage()
        if (!$this->loggedInUser) {
          if (user_access('post comments', $this->web_user)) {
            // The note depends on whether users are actually able to register.
            if ($info['user_register'] != USER_REGISTER_ADMINISTRATORS_ONLY) {
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
            $this->assertLinkByHref("comment/reply/node/$nid/comment#comment-form", 0, 'Comment form link destination is on a separate page.');
            $this->assertNoLinkByHref("node/$nid#comment-form");
          }
          else {
            $this->assertLinkByHref("node/$nid#comment-form", 0, 'Comment form link destination is on node.');
            $this->assertNoLinkByHref("comment/reply/node/$nid/comment#comment-form");
          }
        }

        // Also verify that the comment form appears according to the configured
        // location.
        if ($path == "node/$nid") {
          $elements = $this->xpath('//form[@id=:id]', array(':id' => 'comment-form'));
          if ($info['form'] == COMMENT_FORM_BELOW) {
            $this->assertTrue(count($elements), 'Comment form found below.');
          }
          else {
            $this->assertFalse(count($elements), 'Comment form not found below.');
          }
        }
      }
    }
  }

}
