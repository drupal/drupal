<?php

declare(strict_types=1);

namespace Drupal\Tests\history\Functional;

use Drupal\Tests\comment\Functional\CommentTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests fields on comments.
 */
#[Group('history')]
#[RunTestsInSeparateProcesses]
class CommentFieldsTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests link building with non-default comment field names.
   */
  public function testCommentFieldLinksNonDefaultName(): void {
    $this->drupalCreateContentType(['type' => 'test_node_type']);
    $this->addDefaultCommentField('node', 'test_node_type', 'comment2');

    $web_user = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'create article content',
      'edit own comments',
      'skip comment approval',
      'access content',
    ]);

    // Create a sample node.
    $node = $this->drupalCreateNode([
      'title' => 'Baloney',
      'type' => 'test_node_type',
      'promote' => TRUE,
    ]);

    // Go to the node first so that web_user2 see new comments.
    $this->drupalLogin($web_user);
    $this->drupalGet($node->toUrl());
    $this->postComment($node, 'Here is a comment', '', NULL, 'comment2');

    // We want to check the attached drupalSettings of
    // \Drupal\history\HistoryCommentLinkBuilder::buildCommentedEntityLinks.
    // Therefore we need a node listing, let's use views for that.
    $this->container->get('module_installer')->install(['views', 'history'], TRUE);
    $this->drupalGet('node');

    $link_info = $this->getDrupalSettings()['comment']['newCommentsLinks']['node']['comment2']['2'];
    $this->assertSame(1, $link_info['new_comment_count']);
    $this->assertSame($node->toUrl('canonical', ['fragment' => 'new'])->toString(), $link_info['first_new_comment_link']);
  }

}
