<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\comment\Entity\Comment;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests access controlled node views have the right amount of comment pages.
 *
 * @group node
 */
class NodeAccessPagerTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * An user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $webUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'node_access_test', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    node_access_rebuild();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->addDefaultCommentField('node', 'page');
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'access comments',
      'node test view',
    ]);
  }

  /**
   * Tests the comment pager for nodes with multiple grants per realm.
   */
  public function testCommentPager(): void {
    // Create a node.
    $node = $this->drupalCreateNode();

    // Create 60 comments.
    for ($i = 0; $i < 60; $i++) {
      $comment = Comment::create([
        'entity_id' => $node->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'subject' => $this->randomMachineName(),
        'comment_body' => [
          ['value' => $this->randomMachineName()],
        ],
        'status' => CommentInterface::PUBLISHED,
      ]);
      $comment->save();
    }

    $this->drupalLogin($this->webUser);

    // View the node page. With the default 50 comments per page there should
    // be two pages (0, 1) but no third (2) page.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($node->label());
    $this->assertSession()->pageTextContains('Comments');
    $this->assertSession()->responseContains('page=1');
    $this->assertSession()->responseNotContains('page=2');
  }

}
