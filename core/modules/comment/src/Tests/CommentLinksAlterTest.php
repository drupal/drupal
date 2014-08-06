<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentLinksAlterTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests comment links altering.
 *
 * @group comment
 */
class CommentLinksAlterTest extends CommentTestBase {

  public static $modules = array('comment_test');

  public function setUp() {
    parent::setUp();

    // Enable comment_test.module's hook_comment_links_alter() implementation.
    $this->container->get('state')->set('comment_test_links_alter_enabled', TRUE);
  }

  /**
   * Tests comment links altering.
   */
  public function testCommentLinksAlter() {
    $this->drupalLogin($this->web_user);
    $comment_text = $this->randomMachineName();
    $subject = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $subject);

    $this->drupalGet('node/' . $this->node->id());

    $this->assertLink(t('Report'));
  }

}
