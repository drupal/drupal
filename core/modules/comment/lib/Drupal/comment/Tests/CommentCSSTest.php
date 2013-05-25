<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentCSSTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests comment CSS classes.
 */
class CommentCSSTest extends CommentTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Comment CSS',
      'description' => 'Tests CSS classes on comments.',
      'group' => 'Comment',
    );
  }

  function setUp() {
    parent::setUp();

    // Allow anonymous users to see comments.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments',
      'access content'
    ));
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
        'node_type' => 'node_type_' . $node->bundle(),
        'uid' => $case['comment_uid'],
        'status' => $case['comment_status'],
        'subject' => $this->randomName(),
        'language' => Language::LANGCODE_NOT_SPECIFIED,
        'comment_body' => array(Language::LANGCODE_NOT_SPECIFIED => array($this->randomName())),
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

}
