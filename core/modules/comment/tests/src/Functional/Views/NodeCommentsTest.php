<?php

namespace Drupal\Tests\comment\Functional\Views;

/**
 * Tests comments on nodes.
 *
 * @group comment
 */
class NodeCommentsTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['history'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_new_comments', 'test_comment_count'];

  /**
   * Tests the new comments field plugin.
   */
  public function testNewComments() {
    $this->drupalGet('test-new-comments');
    $this->assertSession()->statusCodeEquals(200);
    $new_comments = $this->cssSelect(".views-field-new-comments a:contains('1')");
    $this->assertCount(1, $new_comments, 'Found the number of new comments for a certain node.');
  }

  /**
   * Test the comment count field.
   */
  public function testCommentCount() {
    $this->drupalGet('test-comment-count');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(2, $this->cssSelect('.views-row'));
    $comment_count_with_comment = $this->cssSelect(".views-field-comment-count span:contains('1')");
    $this->assertCount(1, $comment_count_with_comment);
    $comment_count_without_comment = $this->cssSelect(".views-field-comment-count span:contains('0')");
    $this->assertCount(1, $comment_count_without_comment);

    // Create a content type with no comment field, and add a node.
    $this->drupalCreateContentType(['type' => 'no_comment', 'name' => t('No comment page')]);
    $this->nodeUserPosted = $this->drupalCreateNode(['type' => 'no_comment']);
    $this->drupalGet('test-comment-count');

    // Test that the node with no comment field is also shown.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(3, $this->cssSelect('.views-row'));
    $comment_count_with_comment = $this->cssSelect(".views-field-comment-count span:contains('1')");
    $this->assertCount(1, $comment_count_with_comment);
    $comment_count_without_comment = $this->cssSelect(".views-field-comment-count span:contains('0')");
    $this->assertCount(2, $comment_count_without_comment);
  }

}
