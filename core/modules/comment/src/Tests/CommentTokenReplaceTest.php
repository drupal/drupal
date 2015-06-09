<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentTokenReplaceTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;

/**
 * Generates text using placeholders for dummy content to check comment token
 * replacement.
 *
 * @group comment
 */
class CommentTokenReplaceTest extends CommentTestBase {
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

    $this->drupalLogin($this->adminUser);

    // Set comment variables.
    $this->setCommentSubject(TRUE);

    // Create a node and a comment.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $parent_comment = $this->postComment($node, $this->randomMachineName(), $this->randomMachineName(), TRUE);

    // Post a reply to the comment.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $parent_comment->id());
    $child_comment = $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName());
    $comment = Comment::load($child_comment->id());
    $comment->setHomepage('http://example.org/');

    // Add HTML to ensure that sanitation of some fields tested directly.
    $comment->setSubject('<blink>Blinking Comment</blink>');

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[comment:cid]'] = $comment->id();
    $tests['[comment:hostname]'] = SafeMarkup::checkPlain($comment->getHostname());
    $tests['[comment:author]'] = Xss::filter($comment->getAuthorName());
    $tests['[comment:mail]'] = SafeMarkup::checkPlain($this->adminUser->getEmail());
    $tests['[comment:homepage]'] = check_url($comment->getHomepage());
    $tests['[comment:title]'] = Xss::filter($comment->getSubject());
    $tests['[comment:body]'] = $comment->comment_body->processed;
    $tests['[comment:langcode]'] = SafeMarkup::checkPlain($comment->language()->getId());
    $tests['[comment:url]'] = $comment->url('canonical', $url_options + array('fragment' => 'comment-' . $comment->id()));
    $tests['[comment:edit-url]'] = $comment->url('edit-form', $url_options);
    $tests['[comment:created:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($comment->getCreatedTime(), array('langcode' => $language_interface->getId()));
    $tests['[comment:changed:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($comment->getChangedTimeAcrossTranslations(), array('langcode' => $language_interface->getId()));
    $tests['[comment:parent:cid]'] = $comment->hasParentComment() ? $comment->getParentComment()->id() : NULL;
    $tests['[comment:parent:title]'] = SafeMarkup::checkPlain($parent_comment->getSubject());
    $tests['[comment:entity]'] = SafeMarkup::checkPlain($node->getTitle());
    // Test node specific tokens.
    $tests['[comment:entity:nid]'] = $comment->getCommentedEntityId();
    $tests['[comment:entity:title]'] = SafeMarkup::checkPlain($node->getTitle());
    $tests['[comment:author:uid]'] = $comment->getOwnerId();
    $tests['[comment:author:name]'] = SafeMarkup::checkPlain($this->adminUser->getUsername());

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->getId()));
      $this->assertEqual($output, $expected, format_string('Sanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[comment:hostname]'] = $comment->getHostname();
    $tests['[comment:author]'] = $comment->getAuthorName();
    $tests['[comment:mail]'] = $this->adminUser->getEmail();
    $tests['[comment:homepage]'] = $comment->getHomepage();
    $tests['[comment:title]'] = $comment->getSubject();
    $tests['[comment:body]'] = $comment->comment_body->value;
    $tests['[comment:langcode]'] = $comment->language()->getId();
    $tests['[comment:parent:title]'] = $parent_comment->getSubject();
    $tests['[comment:entity]'] = $node->getTitle();
    $tests['[comment:author:name]'] = $this->adminUser->getUsername();

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->getId(), 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized comment token %token replaced.', array('%token' => $input)));
    }

    // Test anonymous comment author.
    $author_name = $this->randomString();
    $comment->setOwnerId(0)->setAuthorName($author_name);
    $input = '[comment:author]';
    $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->getId()));
    $this->assertEqual($output, Xss::filter($author_name), format_string('Sanitized comment author token %token replaced.', array('%token' => $input)));
    $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->getId(), 'sanitize' => FALSE));
    $this->assertEqual($output, $author_name, format_string('Unsanitized comment author token %token replaced.', array('%token' => $input)));

    // Load node so comment_count gets computed.
    $node = Node::load($node->id());

    // Generate comment tokens for the node (it has 2 comments, both new).
    $tests = array();
    $tests['[entity:comment-count]'] = 2;
    $tests['[entity:comment-count-new]'] = 2;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('entity' => $node, 'node' => $node), array('langcode' => $language_interface->getId()));
      $this->assertEqual($output, $expected, format_string('Node comment token %token replaced.', array('%token' => $input)));
    }
  }

}
