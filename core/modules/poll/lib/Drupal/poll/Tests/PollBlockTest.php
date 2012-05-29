<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollBlockTest.
 */

namespace Drupal\poll\Tests;

class PollBlockTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Block availability',
      'description' => 'Check if the most recent poll block is available.',
      'group' => 'Poll',
    );
  }

  function setUp() {
    parent::setUp(array('block'));

    // Create and login user
    $admin_user = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($admin_user);
  }

  function testRecentBlock() {
    // Set block title to confirm that the interface is available.
    $this->drupalPost('admin/structure/block/manage/poll/recent/configure', array('title' => $this->randomName(8)), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), t('Block configuration set.'));

    // Set the block to a region to confirm block is available.
    $edit = array();
    $edit['blocks[poll_recent][region]'] = 'footer';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'), t('Block successfully move to footer region.'));

    // Create a poll which should appear in recent polls block.
    $title = $this->randomName();
    $choices = $this->_generateChoices(7);
    $poll_nid = $this->pollCreate($title, $choices, TRUE);

    // Verify poll appears in a block.
    // View user page so we're not matching the poll node on front page.
    $this->drupalGet('user');
    // If a 'block' view not generated, this title would not appear even though
    // the choices might.
    $this->assertText($title, 'Poll appears in block.');

    // Logout and login back in as a user who can vote.
    $this->drupalLogout();
    $vote_user = $this->drupalCreateUser(array('cancel own vote', 'inspect all votes', 'vote on polls', 'access content'));
    $this->drupalLogin($vote_user);

    // Verify we can vote via the block.
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPost('user/' . $vote_user->uid, $edit, t('Vote'));
    $this->assertText('Your vote was recorded.', 'Your vote was recorded.');
    $this->assertText('Total votes: 1', 'Vote count updated correctly.');
    $this->assertText('Older polls', 'Link to older polls appears.');
    $this->clickLink('Older polls');
    $this->assertText('1 vote - open', 'Link to poll listing correct.');

    // Close the poll and verify block doesn't appear.
    $content_user = $this->drupalCreateUser(array('create poll content', 'edit any poll content', 'access content'));
    $this->drupalLogout();
    $this->drupalLogin($content_user);
    $close_edit = array('active' => 0);
    $this->pollUpdate($poll_nid, $title, $close_edit);
    $this->drupalGet('user/' . $content_user->uid);
    $this->assertNoText($title, 'Poll no longer appears in block.');
  }
}
