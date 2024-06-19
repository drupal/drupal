<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

/**
 * Test the "approve_comment" views field for approving comments.
 *
 * @group comment
 */
class CommentApproveLinkTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'comment_test_views',
    'system',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_schema'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['comment_test_views']): void {
    parent::setUp($import_test_views, $modules);
  }

  /**
   * Tests that "approve comment" link exists and works as expected.
   */
  public function testCommentApproveLink(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer comments']));
    // Set the comment status to unpublished.
    $this->comment->setUnpublished();
    $this->comment->save();
    $this->drupalGet('/admin/moderate-comments');
    $this->assertSession()->pageTextContains($this->comment->getSubject());
    $this->assertSession()->linkExists('Approve');
    $this->clickLink('Approve');
    $this->drupalGet('/admin/moderate-comments');
    $this->assertSession()->linkNotExists('Approve');
    // Ensure that "published" column in table is marked as yes.
    $this->assertSession()->elementTextContains('xpath', "//table/tbody/tr/td[3]", 'Yes');
  }

}
