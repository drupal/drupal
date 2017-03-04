<?php

namespace Drupal\comment\Tests\Views;

/**
 * Tests the comment row plugin.
 *
 * @group comment
 */
class CommentRowTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_row'];

  /**
   * Test comment row.
   */
  public function testCommentRow() {
    $this->drupalGet('test-comment-row');

    $result = $this->xpath('//article[contains(@class, "comment")]');
    $this->assertEqual(1, count($result), 'One rendered comment found.');
  }

}
