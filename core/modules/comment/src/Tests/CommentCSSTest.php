<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentCSSTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\user\RoleInterface;

/**
 * Tests CSS classes on comments.
 *
 * @group comment
 */
class CommentCSSTest extends CommentTestBase {

  protected function setUp() {
    parent::setUp();

    // Allow anonymous users to see comments.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array(
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
      'node_uid' => array(0, $this->webUser->id()),
      'comment_uid' => array(0, $this->webUser->id(), $this->adminUser->id()),
      'comment_status' => array(CommentInterface::PUBLISHED, CommentInterface::NOT_PUBLISHED),
      'user' => array('anonymous', 'authenticated', 'admin'),
    );
    $permutations = $this->generatePermutations($parameters);

    foreach ($permutations as $case) {
      // Create a new node.
      $node = $this->drupalCreateNode(array('type' => 'article', 'uid' => $case['node_uid']));

      // Add a comment.
      /** @var \Drupal\comment\CommentInterface $comment */
      $comment = entity_create('comment', array(
        'entity_id' => $node->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'uid' => $case['comment_uid'],
        'status' => $case['comment_status'],
        'subject' => $this->randomMachineName(),
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'comment_body' => array(LanguageInterface::LANGCODE_NOT_SPECIFIED => array($this->randomMachineName())),
      ));
      $comment->save();

      // Adjust the current/viewing user.
      switch ($case['user']) {
        case 'anonymous':
          if ($this->loggedInUser) {
            $this->drupalLogout();
          }
          $case['user_uid'] = 0;
          break;

        case 'authenticated':
          $this->drupalLogin($this->webUser);
          $case['user_uid'] = $this->webUser->id();
          break;

        case 'admin':
          $this->drupalLogin($this->adminUser);
          $case['user_uid'] = $this->adminUser->id();
          break;
      }
      // Request the node with the comment.
      $this->drupalGet('node/' . $node->id());
      $settings = $this->getDrupalSettings();

      // Verify the data-history-node-id attribute, which is necessary for the
      // by-viewer class and the "new" indicator, see below.
      $this->assertIdentical(1, count($this->xpath('//*[@data-history-node-id="' . $node->id() . '"]')), 'data-history-node-id attribute is set on node.');

      // Verify classes if the comment is visible for the current user.
      if ($case['comment_status'] == CommentInterface::PUBLISHED || $case['user'] == 'admin') {
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

        // Verify the data-comment-user-id attribute, which is used by the
        // drupal.comment-by-viewer library to add a by-viewer when the current
        // user (the viewer) was the author of the comment. We do this in Java-
        // Script to prevent breaking the render cache.
        $this->assertIdentical(1, count($this->xpath('//*[contains(@class, "comment") and @data-comment-user-id="' . $case['comment_uid'] . '"]')), 'data-comment-user-id attribute is set on comment.');
        $this->assertRaw(drupal_get_path('module', 'comment') . '/js/comment-by-viewer.js', 'drupal.comment-by-viewer library is present.');
      }

      // Verify the unpublished class.
      $comments = $this->xpath('//*[contains(@class, "comment") and contains(@class, "unpublished")]');
      if ($case['comment_status'] == CommentInterface::NOT_PUBLISHED && $case['user'] == 'admin') {
        $this->assertTrue(count($comments) == 1, 'unpublished class found.');
      }
      else {
        $this->assertFalse(count($comments), 'unpublished class not found.');
      }

      // Verify the data-comment-timestamp attribute, which is used by the
      // drupal.comment-new-indicator library to add a "new" indicator to each
      // comment that was created or changed after the last time the current
      // user read the corresponding node.
      if ($case['comment_status'] == CommentInterface::PUBLISHED || $case['user'] == 'admin') {
        $this->assertIdentical(1, count($this->xpath('//*[contains(@class, "comment")]/*[@data-comment-timestamp="' . $comment->getChangedTime() . '"]')), 'data-comment-timestamp attribute is set on comment');
        $expectedJS = ($case['user'] !== 'anonymous');
        $this->assertIdentical($expectedJS, isset($settings['ajaxPageState']['libraries']) && in_array('comment/drupal.comment-new-indicator', explode(',', $settings['ajaxPageState']['libraries'])), 'drupal.comment-new-indicator library is present.');
      }
    }
  }

}
