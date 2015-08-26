<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentTokenReplaceTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Render\BubbleableMetadata;
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
    $tests['[comment:hostname]'] = Html::escape($comment->getHostname());
    $tests['[comment:author]'] = Xss::filter($comment->getAuthorName());
    $tests['[comment:mail]'] = Html::escape($this->adminUser->getEmail());
    $tests['[comment:homepage]'] = check_url($comment->getHomepage());
    $tests['[comment:title]'] = Xss::filter($comment->getSubject());
    $tests['[comment:body]'] = $comment->comment_body->processed;
    $tests['[comment:langcode]'] = Html::escape($comment->language()->getId());
    $tests['[comment:url]'] = $comment->url('canonical', $url_options + array('fragment' => 'comment-' . $comment->id()));
    $tests['[comment:edit-url]'] = $comment->url('edit-form', $url_options);
    $tests['[comment:created]'] = \Drupal::service('date.formatter')->format($comment->getCreatedTime(), 'medium', array('langcode' => $language_interface->getId()));
    $tests['[comment:created:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($comment->getCreatedTime(), array('langcode' => $language_interface->getId()));
    $tests['[comment:changed:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($comment->getChangedTimeAcrossTranslations(), array('langcode' => $language_interface->getId()));
    $tests['[comment:parent:cid]'] = $comment->hasParentComment() ? $comment->getParentComment()->id() : NULL;
    $tests['[comment:parent:title]'] = Html::escape($parent_comment->getSubject());
    $tests['[comment:entity]'] = Html::escape($node->getTitle());
    // Test node specific tokens.
    $tests['[comment:entity:nid]'] = $comment->getCommentedEntityId();
    $tests['[comment:entity:title]'] = Html::escape($node->getTitle());
    $tests['[comment:author:uid]'] = $comment->getOwnerId();
    $tests['[comment:author:name]'] = Html::escape($this->adminUser->getUsername());

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($comment);
    $metadata_tests = [];
    $metadata_tests['[comment:cid]'] = $base_bubbleable_metadata;
    $metadata_tests['[comment:hostname]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $bubbleable_metadata->addCacheableDependency($this->adminUser);
    $metadata_tests['[comment:author]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $bubbleable_metadata->addCacheableDependency($this->adminUser);
    $metadata_tests['[comment:mail]'] = $bubbleable_metadata;
    $metadata_tests['[comment:homepage]'] = $base_bubbleable_metadata;
    $metadata_tests['[comment:title]'] = $base_bubbleable_metadata;
    $metadata_tests['[comment:body]'] = $base_bubbleable_metadata;
    $metadata_tests['[comment:langcode]'] = $base_bubbleable_metadata;
    $metadata_tests['[comment:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[comment:edit-url]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[comment:created]'] = $bubbleable_metadata->addCacheTags(['rendered']);
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[comment:created:since]'] = $bubbleable_metadata->setCacheMaxAge(0);
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[comment:changed:since]'] = $bubbleable_metadata->setCacheMaxAge(0);
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[comment:parent:cid]'] = $bubbleable_metadata->addCacheTags(['comment:1']);
    $metadata_tests['[comment:parent:title]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[comment:entity]'] = $bubbleable_metadata->addCacheTags(['node:2']);
    // Test node specific tokens.
    $metadata_tests['[comment:entity:nid]'] = $bubbleable_metadata;
    $metadata_tests['[comment:entity:title]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[comment:author:uid]'] = $bubbleable_metadata->addCacheTags(['user:2']);
    $metadata_tests['[comment:author:name]'] = $bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_service->replace($input, array('comment' => $comment), array('langcode' => $language_interface->getId()), $bubbleable_metadata);
      $this->assertEqual($output, $expected, format_string('Sanitized comment token %token replaced.', array('%token' => $input)));
      $this->assertEqual($bubbleable_metadata, $metadata_tests[$input]);
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
