<?php

namespace Drupal\Tests\comment\Functional;

/**
 * Tests comment links altering.
 *
 * @group comment
 */
class CommentLinksAlterTest extends CommentTestBase {

  protected static $modules = ['comment_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Enable comment_test.module's hook_comment_links_alter() implementation.
    $this->container->get('state')->set('comment_test_links_alter_enabled', TRUE);
  }

  /**
   * Tests comment links altering.
   */
  public function testCommentLinksAlter() {
    $this->drupalLogin($this->webUser);
    $comment_text = $this->randomMachineName();
    $subject = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $subject);

    $this->drupalGet('node/' . $this->node->id());

    $this->assertSession()->linkExists(t('Report'));
  }

}
