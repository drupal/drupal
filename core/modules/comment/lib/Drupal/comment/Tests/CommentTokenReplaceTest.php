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
    $language_interface = language(Language::TYPE_INTERFACE);
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
    $this->drupalGet('comment/reply/' . $node->nid . '/' . $parent_comment->id());
    $child_comment = $this->postComment(NULL, $this->randomName(), $this->randomName());
    $comment = comment_load($child_comment->id());
    $comment->homepage->value = 'http://example.org/';

    // Add HTML to ensure that sanitation of some fields tested directly.
    $comment->subject->value = '<blink>Blinking Comment</blink>';
    $instance = field_info_instance('comment', 'body', 'comment_body');

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[comment:cid]'] = $comment->id();
    $tests['[comment:hostname]'] = check_plain($comment->hostname->value);
    $tests['[comment:name]'] = filter_xss($comment->name->value);
    $tests['[comment:mail]'] = check_plain($this->admin_user->mail);
    $tests['[comment:homepage]'] = check_url($comment->homepage->value);
    $tests['[comment:title]'] = filter_xss($comment->subject->value);
    $tests['[comment:body]'] = $comment->comment_body->processed;
    $tests['[comment:url]'] = url('comment/' . $comment->id(), $url_options + array('fragment' => 'comment-' . $comment->id()));
    $tests['[comment:edit-url]'] = url('comment/' . $comment->id() . '/edit', $url_options);
    $tests['[comment:created:since]'] = format_interval(REQUEST_TIME - $comment->created->value, 2, $language_interface->id);
    $tests['[comment:changed:since]'] = format_interval(REQUEST_TIME - $comment->changed->value, 2, $language_interface->id);
    $tests['[comment:parent:cid]'] = $comment->pid->target_id;
    $tests['[comment:parent:title]'] = check_plain($parent_comment->subject->value);
    $tests['[comment:node:nid]'] = $comment->nid->target_id;
    $tests['[comment:node:title]'] = check_plain($node->title);
    $tests['[comment:author:uid]'] = $comment->uid->target_id;
    $tests['[comment:author:name]'] = check_plain($this->admin_user->name);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[comment:hostname]'] = $comment->hostname->value;
    $tests['[comment:name]'] = $comment->name->value;
    $tests['[comment:mail]'] = $this->admin_user->mail;
    $tests['[comment:homepage]'] = $comment->homepage->value;
    $tests['[comment:title]'] = $comment->subject->value;
    $tests['[comment:body]'] = $comment->comment_body->value;
    $tests['[comment:parent:title]'] = $parent_comment->subject->value;
    $tests['[comment:node:title]'] = $node->title;
    $tests['[comment:author:name]'] = $this->admin_user->name;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Load node so comment_count gets computed.
    $node = node_load($node->nid);

    // Generate comment tokens for the node (it has 2 comments, both new).
    $tests = array();
    $tests['[node:comment-count]'] = 2;
    $tests['[node:comment-count-new]'] = 2;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('node' => $node), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Node comment token %token replaced.', array('%token' => $input)));
    }
  }
}
