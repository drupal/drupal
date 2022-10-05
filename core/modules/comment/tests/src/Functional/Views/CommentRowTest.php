<?php

namespace Drupal\Tests\comment\Functional\Views;

/**
 * Tests the comment row plugin.
 *
 * @group comment
 */
class CommentRowTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_row'];

  /**
   * Tests comment row.
   */
  public function testCommentRow() {
    $this->drupalGet('test-comment-row');
    $this->assertSession()->elementsCount('xpath', '//article[contains(@class, "comment")]', 1);
  }

}
