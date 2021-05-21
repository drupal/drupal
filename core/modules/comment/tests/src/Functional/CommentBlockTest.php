<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\user\RoleInterface;

/**
 * Tests comment block functionality.
 *
 * @group comment
 */
class CommentBlockTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    // Update admin user to have the 'administer blocks' permission.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer comments',
      'skip comment approval',
      'post comments',
      'access comments',
      'access content',
      'administer blocks',
     ]);
  }

  /**
   * Tests the recent comments block.
   */
  public function testRecentCommentBlock() {
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('views_block:comments_recent-block_1');

    // Add some test comments, with and without subjects. Because the 10 newest
    // comments should be shown by the block, we create 11 to test that behavior
    // below.
    $timestamp = REQUEST_TIME;
    for ($i = 0; $i < 11; ++$i) {
      $subject = ($i % 2) ? $this->randomMachineName() : '';
      $comments[$i] = $this->postComment($this->node, $this->randomMachineName(), $subject);
      $comments[$i]->created->value = $timestamp--;
      $comments[$i]->save();
    }

    // Test that a user without the 'access comments' permission cannot see the
    // block.
    $this->drupalLogout();
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['access comments']);
    $this->drupalGet('');
    $this->assertNoText('Recent comments');
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access comments']);

    // Test that a user with the 'access comments' permission can see the
    // block.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Recent comments');

    // Test the only the 10 latest comments are shown and in the proper order.
    $this->assertNoText($comments[10]->getSubject());
    for ($i = 0; $i < 10; $i++) {
      $this->assertSession()->pageTextContains($comments[$i]->getSubject());
      if ($i > 1) {
        $previous_position = $position;
        $position = strpos($this->getSession()->getPage()->getContent(), $comments[$i]->getSubject());
        $this->assertGreaterThan($previous_position, $position, sprintf('Comment %d does not appear after comment %d', 10 - $i, 11 - $i));
      }
      $position = strpos($this->getSession()->getPage()->getContent(), $comments[$i]->getSubject());
    }

    // Test that links to comments work when comments are across pages.
    $this->setCommentsPerPage(1);

    for ($i = 0; $i < 10; $i++) {
      $this->clickLink($comments[$i]->getSubject());
      $this->assertSession()->pageTextContains($comments[$i]->getSubject());
      $this->assertRaw('<link rel="canonical"');
    }
  }

}
