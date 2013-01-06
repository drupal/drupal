<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentBlockTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests the Comment module blocks.
 */
class CommentBlockTest extends CommentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  function setUp() {
    parent::setUp();
    // Update admin user to have the 'administer blocks' permission.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer content types',
      'administer comments',
      'skip comment approval',
      'post comments',
      'access comments',
      'access content',
      'administer blocks',
     ));
  }

  public static function getInfo() {
    return array(
      'name' => 'Comment blocks',
      'description' => 'Test comment block functionality.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests the recent comments block.
   */
  function testRecentCommentBlock() {
    $this->drupalLogin($this->admin_user);
    $current_theme = variable_get('default_theme', 'stark');
    $machine_name = 'test_recent_comments';
    $edit = array(
      'machine_name' => $machine_name,
      'region' => 'sidebar_first',
      'title' => $this->randomName(),
      'block_count' => 2,
    );
    $this->drupalPost('admin/structure/block/manage/recent_comments/' . $current_theme, $edit, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block was saved.');

    // Add some test comments, one without a subject.
    $comment1 = $this->postComment($this->node, $this->randomName(), $this->randomName());
    $comment2 = $this->postComment($this->node, $this->randomName(), $this->randomName());
    $comment3 = $this->postComment($this->node, $this->randomName());

    // Test that a user without the 'access comments' permission cannot see the
    // block.
    $this->drupalLogout();
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access comments'));
    // drupalCreateNode() does not automatically flush content caches unlike
    // posting a node from a node form.
    cache_invalidate_tags(array('content' => TRUE));
    $this->drupalGet('');
    $this->assertNoText($edit['title'], 'Block was not found.');
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access comments'));

    // Test that a user with the 'access comments' permission can see the
    // block.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('');
    $this->assertText($edit['title'], 'Block was found.');

    // Test the only the 2 latest comments are shown and in the proper order.
    $this->assertNoText($comment1->subject, 'Comment not found in block.');
    $this->assertText($comment2->subject, 'Comment found in block.');
    $this->assertText($comment3->comment, 'Comment found in block.');
    $this->assertTrue(strpos($this->drupalGetContent(), $comment3->comment) < strpos($this->drupalGetContent(), $comment2->subject), 'Comments were ordered correctly in block.');

    // Set the number of recent comments to show to 10.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $block = array(
      'block_count' => 10,
    );

    $this->drupalPost("admin/structure/block/manage/plugin.core.block.$current_theme.$machine_name/$current_theme/configure", $block, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block saved.');

    // Post an additional comment.
    $comment4 = $this->postComment($this->node, $this->randomName(), $this->randomName());

    // Test that all four comments are shown.
    $this->assertText($comment1->subject, 'Comment found in block.');
    $this->assertText($comment2->subject, 'Comment found in block.');
    $this->assertText($comment3->comment, 'Comment found in block.');
    $this->assertText($comment4->subject, 'Comment found in block.');

    // Test that links to comments work when comments are across pages.
    $this->setCommentsPerPage(1);
    $this->drupalGet('');
    $this->clickLink($comment1->subject);
    $this->assertText($comment1->subject, 'Comment link goes to correct page.');
    $this->drupalGet('');
    $this->clickLink($comment2->subject);
    $this->assertText($comment2->subject, 'Comment link goes to correct page.');
    $this->clickLink($comment4->subject);
    $this->assertText($comment4->subject, 'Comment link goes to correct page.');
    // Check that when viewing a comment page from a link to the comment, that
    // rel="canonical" is added to the head of the document.
    $this->assertRaw('<link rel="canonical"', 'Canonical URL was found in the HTML head');
  }
}
