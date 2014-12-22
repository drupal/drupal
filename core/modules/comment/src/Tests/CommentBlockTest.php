<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentBlockTest.
 */

namespace Drupal\comment\Tests;
use Drupal\Component\Utility\String;

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
  public static $modules = array('block', 'views');

  protected function setUp() {
    parent::setUp();
    // Update admin user to have the 'administer blocks' permission.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer content types',
      'administer comments',
      'skip comment approval',
      'post comments',
      'access comments',
      'access content',
      'administer blocks',
     ));
  }

  /**
   * Tests the recent comments block.
   */
  function testRecentCommentBlock() {
    $this->drupalLogin($this->adminUser);
    $block = $this->drupalPlaceBlock('views_block:comments_recent-block_1');

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
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access comments'));
    $this->drupalGet('');
    $this->assertNoText(t('Recent comments'));
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access comments'));

    // Test that a user with the 'access comments' permission can see the
    // block.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('');
    $this->assertText(t('Recent comments'));

    // Test the only the 10 latest comments are shown and in the proper order.
    $this->assertNoText($comments[10]->getSubject(), 'Comment 11 not found in block.');
    for ($i = 0; $i < 10; $i++) {
      $this->assertText($comments[$i]->getSubject(), String::format('Comment @number found in block.', array('@number' => 10 - $i)));
      if ($i > 1) {
        $previous_position = $position;
        $position = strpos($this->drupalGetContent(), $comments[$i]->getSubject());
        $this->assertTrue($position > $previous_position, String::format('Comment @a appears after comment @b', array('@a' => 10 - $i, '@b' => 11 - $i)));
      }
      $position = strpos($this->drupalGetContent(), $comments[$i]->getSubject());
    }

    // Test that links to comments work when comments are across pages.
    $this->setCommentsPerPage(1);

    for ($i = 0; $i < 10; $i++) {
      $this->clickLink($comments[$i]->getSubject());
      $this->assertText($comments[$i]->getSubject(), 'Comment link goes to correct page.');
      $this->assertRaw('<link rel="canonical"', 'Canonical URL was found in the HTML head');
    }
  }

}
