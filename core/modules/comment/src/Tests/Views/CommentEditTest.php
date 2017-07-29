<?php

namespace Drupal\comment\Tests\Views;

use Drupal\comment\Tests\CommentTestBase as CommentWebTestBase;

/**
 * Tests comment edit functionality.
 *
 * @group comment
 */
class CommentEditTest extends CommentWebTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Tests comment label in admin view.
   */
  public function testCommentEdit() {
    $this->drupalLogin($this->adminUser);
    // Post a comment to node.
    $node_comment = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName(), TRUE);
    $this->drupalGet('admin/content/comment');
    $this->assertText($this->adminUser->label());
    $this->drupalGet($node_comment->toUrl('edit-form')->toString());
    $edit = [
      'comment_body[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/content/comment');
    $this->assertText($this->adminUser->label());
  }

}
