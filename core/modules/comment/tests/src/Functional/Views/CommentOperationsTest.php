<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

/**
 * Tests comment operations.
 *
 * @group comment
 */
class CommentOperationsTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_operations'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the operations field plugin.
   */
  public function testCommentOperations(): void {
    $admin_account = $this->drupalCreateUser(['administer comments']);
    $this->drupalLogin($admin_account);
    $this->drupalGet('test-comment-operations');
    $this->assertSession()->statusCodeEquals(200);
    // Assert Edit operation is present.
    $this->assertSession()->elementsCount('xpath', '//td[contains(@class, "views-field-operations")]//li/a[text() = "Edit"]', 1);
    // Assert Delete operation is present.
    $this->assertSession()->elementsCount('xpath', '//td[contains(@class, "views-field-operations")]//li/a[text() = "Delete"]', 1);
  }

}
