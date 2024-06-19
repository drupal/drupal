<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;

/**
 * Tests that comments behave correctly when added as entity references.
 *
 * @group comment
 */
class CommentEntityReferenceTest extends CommentTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A second test node containing references to comments.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node2;

  /**
   * A comment linked to a node.
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $comment;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createEntityReferenceField(
      'node',
      'article',
      'entity_reference_comment',
      'Entity Reference Comment',
      'comment',
      'default',
      ['target_bundles' => ['comment']]
    );
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('entity_reference_comment', ['type' => 'options_select'])
      ->save();
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article')
      ->setComponent('entity_reference_comment', ['type' => 'entity_reference_label'])
      ->save();

    $administratorUser = $this->drupalCreateUser([
      'skip comment approval',
      'post comments',
      'access comments',
      'access content',
      'administer nodes',
      'administer comments',
      'bypass node access',
    ]);
    $this->drupalLogin($administratorUser);

    $this->node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1, 'uid' => $this->webUser->id()]);
    $this->comment = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName());
    $this->assertInstanceOf(Comment::class, $this->comment);

    $this->node2 = $this->drupalCreateNode([
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ]);
  }

  /**
   * Tests that comments are correctly saved as entity references.
   */
  public function testCommentAsEntityReference(): void {
    // Load the node and save it.
    $edit = [
      'entity_reference_comment' => $this->comment->id(),
    ];
    $this->drupalGet('node/' . $this->node2->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('has been updated');

    // Make sure the comment is linked.
    $this->assertSession()->pageTextContains($this->comment->label());
  }

  /**
   * Tests that comments of unpublished are not shown.
   */
  public function testCommentOfUnpublishedNodeBypassAccess(): void {
    // Unpublish the node that has the comment.
    $this->node->setUnpublished()->save();

    // When the user has 'bypass node access' permission, they can still set it.
    $edit = [
      'entity_reference_comment' => $this->comment->id(),
    ];
    $this->drupalGet('node/' . $this->node2->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('has been updated');

    // Comment is seen as administrator user.
    $this->assertSession()->pageTextContains($this->comment->label());

    // But not as anonymous.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node2->id());
    $this->assertSession()->pageTextContains($this->node2->label());
    $this->assertSession()->pageTextNotContains($this->comment->label());
  }

}
