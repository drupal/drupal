<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollVoteTest.
 */

namespace Drupal\poll\Tests;

class PollVoteTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Poll vote',
      'description' => 'Vote on a poll',
      'group' => 'Poll'
    );
  }

  function tearDown() {
    parent::tearDown();
  }

  function testPollVote() {
    $title = $this->randomName();
    $choices = $this->_generateChoices(7);
    $poll_nid = $this->pollCreate($title, $choices, FALSE);
    $this->drupalLogout();

    $vote_user = $this->drupalCreateUser(array('cancel own vote', 'inspect all votes', 'vote on polls', 'access content'));
    $restricted_vote_user = $this->drupalCreateUser(array('vote on polls', 'access content'));

    $this->drupalLogin($vote_user);

    // Record a vote for the first choice.
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPost('node/' . $poll_nid, $edit, t('Vote'));
    $this->assertText('Your vote was recorded.', 'Your vote was recorded.');
    $this->assertText('Total votes: 1', 'Vote count updated correctly.');
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(isset($elements[0]), t("'Cancel your vote' button appears."));

    $this->drupalGet("node/$poll_nid/votes");
    $this->assertText(t('This table lists all the recorded votes for this poll. If anonymous users are allowed to vote, they will be identified by the IP address of the computer they used when they voted.'), 'Vote table text.');
    $this->assertText($choices[0], 'Vote recorded');

    // Ensure poll listing page has correct number of votes.
    $this->drupalGet('poll');
    $this->assertText($title, 'Poll appears in poll list.');
    $this->assertText('1 vote', 'Poll has 1 vote.');

    // Cancel a vote.
    $this->drupalPost('node/' . $poll_nid, array(), t('Cancel your vote'));
    $this->assertText('Your vote was cancelled.', 'Your vote was cancelled.');
    $this->assertNoText('Cancel your vote', "Cancel vote button doesn't appear.");

    $this->drupalGet("node/$poll_nid/votes");
    $this->assertNoText($choices[0], 'Vote cancelled');

    // Ensure poll listing page has correct number of votes.
    $this->drupalGet('poll');
    $this->assertText($title, 'Poll appears in poll list.');
    $this->assertText('0 votes', 'Poll has 0 votes.');

    // Log in as a user who can only vote on polls.
    $this->drupalLogout();
    $this->drupalLogin($restricted_vote_user);

    // Vote on a poll.
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPost('node/' . $poll_nid, $edit, t('Vote'));
    $this->assertText('Your vote was recorded.', 'Your vote was recorded.');
    $this->assertText('Total votes: 1', 'Vote count updated correctly.');
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(empty($elements), t("'Cancel your vote' button does not appear."));
  }
}
