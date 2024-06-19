<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

use Drupal\Tests\comment\Functional\CommentTestBase as CommentBrowserTestBase;

/**
 * Tests comment edit functionality.
 *
 * @group comment
 */
class CommentEditTest extends CommentBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests comment label in admin view.
   */
  public function testCommentEdit(): void {
    $this->drupalLogin($this->adminUser);
    // Post a comment to node.
    $node_comment = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $this->drupalGet('admin/content/comment');
    $this->assertSession()->pageTextContains($this->adminUser->label());
    $this->drupalGet($node_comment->toUrl('edit-form'));
    $edit = [
      'comment_body[0][value]' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/content/comment');
    $this->assertSession()->pageTextContains($this->adminUser->label());
  }

}
