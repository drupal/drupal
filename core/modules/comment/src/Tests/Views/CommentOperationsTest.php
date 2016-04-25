<?php

namespace Drupal\comment\Tests\Views;

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
   * Test the operations field plugin.
   */
  public function testCommentOperations() {
    $admin_account = $this->drupalCreateUser(['administer comments']);
    $this->drupalLogin($admin_account);
    $this->drupalGet('test-comment-operations');
    $this->assertResponse(200);
    $operation = $this->cssSelect('.views-field-operations li.edit a');
    $this->assertEqual(count($operation), 1, 'Found edit operation for comment.');
    $operation = $this->cssSelect('.views-field-operations li.delete a');
    $this->assertEqual(count($operation), 1, 'Found delete operation for comment.');
  }

}
