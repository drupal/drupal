<?php

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
  protected $defaultTheme = 'classy';

  /**
   * Test the operations field plugin.
   */
  public function testCommentOperations() {
    $admin_account = $this->drupalCreateUser(['administer comments']);
    $this->drupalLogin($admin_account);
    $this->drupalGet('test-comment-operations');
    $this->assertSession()->statusCodeEquals(200);
    $operation = $this->cssSelect('.views-field-operations li.edit a');
    $this->assertCount(1, $operation, 'Found edit operation for comment.');
    $operation = $this->cssSelect('.views-field-operations li.delete a');
    $this->assertCount(1, $operation, 'Found delete operation for comment.');
  }

}
