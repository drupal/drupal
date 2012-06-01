<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentTokenReplaceTest.
 */

namespace Drupal\comment\Tests;

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
    global $language_interface;
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
    $this->drupalGet('comment/reply/' . $node->nid . '/' . $parent_comment->id);
    $child_comment = $this->postComment(NULL, $this->randomName(), $this->randomName());
    $comment = comment_load($child_comment->id);
    $comment->homepage = 'http://example.org/';

    // Add HTML to ensure that sanitation of some fields tested directly.
    $comment->subject = '<blink>Blinking Comment</blink>';
    $instance = field_info_instance('comment', 'body', 'comment_body');

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[comment:cid]'] = $comment->cid;
    $tests['[comment:hostname]'] = check_plain($comment->hostname);
    $tests['[comment:name]'] = filter_xss($comment->name);
    $tests['[comment:mail]'] = check_plain($this->admin_user->mail);
    $tests['[comment:homepage]'] = check_url($comment->homepage);
    $tests['[comment:title]'] = filter_xss($comment->subject);
    $tests['[comment:body]'] = _text_sanitize($instance, LANGUAGE_NOT_SPECIFIED, $comment->comment_body[LANGUAGE_NOT_SPECIFIED][0], 'value');
    $tests['[comment:url]'] = url('comment/' . $comment->cid, $url_options + array('fragment' => 'comment-' . $comment->cid));
    $tests['[comment:edit-url]'] = url('comment/' . $comment->cid . '/edit', $url_options);
    $tests['[comment:created:since]'] = format_interval(REQUEST_TIME - $comment->created, 2, $language_interface->langcode);
    $tests['[comment:changed:since]'] = format_interval(REQUEST_TIME - $comment->changed, 2, $language_interface->langcode);
    $tests['[comment:parent:cid]'] = $comment->pid;
    $tests['[comment:parent:title]'] = check_plain($parent_comment->subject);
    $tests['[comment:node:nid]'] = $comment->nid;
    $tests['[comment:node:title]'] = check_plain($node->title);
    $tests['[comment:author:uid]'] = $comment->uid;
    $tests['[comment:author:name]'] = check_plain($this->admin_user->name);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), t('No empty tokens generated.'));

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('comment' => $comment), array('language' => $language_interface));
      $this->assertEqual($output, $expected, t('Sanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[comment:hostname]'] = $comment->hostname;
    $tests['[comment:name]'] = $comment->name;
    $tests['[comment:mail]'] = $this->admin_user->mail;
    $tests['[comment:homepage]'] = $comment->homepage;
    $tests['[comment:title]'] = $comment->subject;
    $tests['[comment:body]'] = $comment->comment_body[LANGUAGE_NOT_SPECIFIED][0]['value'];
    $tests['[comment:parent:title]'] = $parent_comment->subject;
    $tests['[comment:node:title]'] = $node->title;
    $tests['[comment:author:name]'] = $this->admin_user->name;

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('comment' => $comment), array('language' => $language_interface, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, t('Unsanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Load node so comment_count gets computed.
    $node = node_load($node->nid);

    // Generate comment tokens for the node (it has 2 comments, both new).
    $tests = array();
    $tests['[node:comment-count]'] = 2;
    $tests['[node:comment-count-new]'] = 2;

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('node' => $node), array('language' => $language_interface));
      $this->assertEqual($output, $expected, t('Node comment token %token replaced.', array('%token' => $input)));
    }
  }
}
