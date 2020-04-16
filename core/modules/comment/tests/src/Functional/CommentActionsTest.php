<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\system\Entity\Action;

/**
 * Tests actions provided by the Comment module.
 *
 * @group comment
 */
class CommentActionsTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['dblog', 'action'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests comment publish and unpublish actions.
   */
  public function testCommentPublishUnpublishActions() {
    $this->drupalLogin($this->webUser);
    $comment_text = $this->randomMachineName();
    $subject = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $subject);

    // Unpublish a comment.
    $action = Action::load('comment_unpublish_action');
    $action->execute([$comment]);
    $this->assertTrue($comment->isPublished() === FALSE, 'Comment was unpublished');
    $this->assertSame(['module' => ['comment']], $action->getDependencies());
    // Publish a comment.
    $action = Action::load('comment_publish_action');
    $action->execute([$comment]);
    $this->assertTrue($comment->isPublished() === TRUE, 'Comment was published');
  }

  /**
   * Tests the unpublish comment by keyword action.
   */
  public function testCommentUnpublishByKeyword() {
    $this->drupalLogin($this->adminUser);
    $keyword_1 = $this->randomMachineName();
    $keyword_2 = $this->randomMachineName();
    $action = Action::create([
      'id' => 'comment_unpublish_by_keyword_action',
      'label' => $this->randomMachineName(),
      'type' => 'comment',
      'configuration' => [
        'keywords' => [$keyword_1, $keyword_2],
      ],
      'plugin' => 'comment_unpublish_by_keyword_action',
    ]);
    $action->save();

    $comment = $this->postComment($this->node, $keyword_2, $this->randomMachineName());

    // Load the full comment so that status is available.
    $comment = Comment::load($comment->id());

    $this->assertTrue($comment->isPublished() === TRUE, 'The comment status was set to published.');

    $action->execute([$comment]);
    $this->assertTrue($comment->isPublished() === FALSE, 'The comment status was set to not published.');
  }

}
