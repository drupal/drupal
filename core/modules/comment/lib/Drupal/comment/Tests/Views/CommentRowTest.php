<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\CommentRowTest.
 */

namespace Drupal\comment\Tests\Views;

/**
 * Tests the comment row plugin.
 */
class CommentRowTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_comment_row');

  public static function getInfo() {
    return array(
      'name' => 'Comment: Row Plugin',
      'description' => 'Tests the comment row plugin.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Test comment row.
   */
  public function testCommentRow() {
    $this->drupalGet('test-comment-row');

    $result = $this->xpath('//article[contains(@class, "comment")]');
    $this->assertEqual(1, count($result), 'One rendered comment found.');
  }

}
