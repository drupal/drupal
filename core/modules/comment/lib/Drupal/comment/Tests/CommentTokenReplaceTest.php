<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentTokenReplaceTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests comment token replacement in strings.
 */
class CommentTokenReplaceTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment token replacement',
      'description' => 'Generates text using placeholders for dummy content to check comment token replacement.',
      'group' => 'Comment',
    );
  }

  /**
   * Creates a comment, then tests the tokens generated from it.
   */
  function testCommentTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $url_options = array(
      'absolute' => TRUE,
      'language' => $language_interface,
    );

    $this->drupalLogin($this->admin_user);

    // Set comment variables.
    $this->setCommentSubject(TRUE);

    // Create a node and a comment.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $parent_comment = $this->postComment($node, $this->randomName(), $this->randomName(), TRUE);

    // Post a reply to the comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $parent_comment->id());
    $child_comment = $this->postComment(NULL, $this->randomName(), $this->randomName());
    $comment = comment_load($child_comment->id());
    $comment->setHomepage('http://example.org/');

    // Add HTML to ensure that sanitation of some fields tested directly.
    $comment->setSubject('<blink>Blinking Comment</blink>');

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[comment:cid]'] = $comment->id();
    $tests['[comment:hostname]'] = check_plain($comment->getHostname());
    $tests['[comment:name]'] = filter_xss($comment->getAuthorName());
    $tests['[comment:author]'] = filter_xss($comment->getAuthorName());
    $tests['[comment:mail]'] = check_plain($this->admin_user->getEmail());
    $tests['[comment:homepage]'] = check_url($comment->getHomepage());
    $tests['[comment:title]'] = filter_xss($comment->getSubject());
    $tests['[comment:body]'] = $comment->comment_body->processed;
    $tests['[comment:url]'] = url('comment/' . $comment->id(), $url_options + array('fragment' => 'comment-' . $comment->id()));
    $tests['[comment:edit-url]'] = url('comment/' . $comment->id() . '/edit', $url_options);
    $tests['[comment:created:since]'] = format_interval(REQUEST_TIME - $comment->getCreatedTime(), 2, $language_interface->id);
    $tests['[comment:changed:since]'] = format_interval(REQUEST_TIME - $comment->getChangedTime(), 2, $language_interface->id);
    $tests['[comment:parent:cid]'] = $comment->hasParentComment() ? $comment->getParentComment()->id() : NULL;
    $tests['[comment:parent:title]'] = check_plain($parent_comment->getSubject());
    $tests['[comment:node:nid]'] = $comment->getCommentedEntityId();
    $tests['[comment:node:title]'] = check_plain($node->getTitle());
    $tests['[comment:author:uid]'] = $comment->getOwnerId();
    $tests['[comment:author:name]'] = check_plain($this->admin_user->getUsername());

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[comment:hostname]'] = $comment->getHostname();
    $tests['[comment:name]'] = $comment->getAuthorName();
    $tests['[comment:author]'] = $comment->getAuthorName();
    $tests['[comment:mail]'] = $this->admin_user->getEmail();
    $tests['[comment:homepage]'] = $comment->getHomepage();
    $tests['[comment:title]'] = $comment->getSubject();
    $tests['[comment:body]'] = $comment->comment_body->value;
    $tests['[comment:parent:title]'] = $parent_comment->getSubject();
    $tests['[comment:node:title]'] = $node->getTitle();
    $tests['[comment:author:name]'] = $this->admin_user->getUsername();

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Load node so comment_count gets computed.
    $node = node_load($node->id());

    // Generate comment tokens for the node (it has 2 comments, both new).
    $tests = array();
    $tests['[entity:comment-count]'] = 2;
    $tests['[entity:comment-count-new]'] = 2;
    // Also test the deprecated legacy token.
    $tests['[node:comment-count]'] = 2;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('entity' => $node, 'node' => $node), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Node comment token %token replaced.', array('%token' => $input)));
    }
  }
}
