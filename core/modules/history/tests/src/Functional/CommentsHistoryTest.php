<?php

declare(strict_types=1);

namespace Drupal\Tests\history\Functional;

use Drupal\Tests\comment\Functional\Views\CommentTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests comments history.
 */
#[Group('history')]
#[RunTestsInSeparateProcesses]
class CommentsHistoryTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['history', 'history_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_new_comments'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['history_test_views']): void {
    parent::setUp($import_test_views, $modules);
  }

  /**
   * Tests the new comments field plugin.
   */
  public function testNewComments(): void {
    $this->drupalGet('test-new-comments');
    $this->assertSession()->statusCodeEquals(200);
    $new_comments = $this->cssSelect(".views-field-new-comments a:contains('1')");
    $this->assertCount(1, $new_comments, 'Found the number of new comments for a certain node.');
  }

}
