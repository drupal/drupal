<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the comment row plugin.
 */
#[Group('comment')]
#[RunTestsInSeparateProcesses]
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
  public function testCommentRow(): void {
    $this->drupalGet('test-comment-row');
    $this->assertSession()->elementsCount('xpath', '//article[contains(@class, "comment")]', 1);
  }

}
