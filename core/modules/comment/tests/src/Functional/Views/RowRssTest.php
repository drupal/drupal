<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the comment rss row plugin.
 *
 * @see \Drupal\comment\Plugin\views\row\Rss
 */
#[Group('comment')]
#[RunTestsInSeparateProcesses]
class RowRssTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_rss'];

  /**
   * Tests comment rss output.
   */
  public function testRssRow(): void {
    $this->drupalGet('test-comment-rss');

    // Because the response is XML we can't use the page which depends on an
    // HTML tag being present.
    $result = $this->getSession()->getDriver()->find('//item');
    $this->assertCount(1, $result, 'Just one comment was found in the rss output.');

    $this->assertEquals(gmdate('r', $this->comment->getCreatedTime()), $result[0]->find('xpath', '//pubDate')->getHtml(), 'The right pubDate appears in the rss output.');
  }

}
