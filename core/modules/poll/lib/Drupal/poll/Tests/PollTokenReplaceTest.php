<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollTokenReplaceTest.
 */

namespace Drupal\poll\Tests;

/**
 * Test poll token replacement in strings.
 */
class PollTokenReplaceTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Poll token replacement',
      'description' => 'Generates text using placeholders for dummy content to check poll token replacement.',
      'group' => 'Poll',
    );
  }

  /**
   * Creates a poll, then tests the tokens generated from it.
   */
  function testPollTokenReplacement() {
    $language_interface = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);

    // Craete a poll with three choices.
    $title = $this->randomName();
    $choices = $this->_generateChoices(3);
    $poll_nid = $this->pollCreate($title, $choices, FALSE);
    $this->drupalLogout();

    // Create four users and have each of them vote.
    $vote_user1 = $this->drupalCreateUser(array('vote on polls', 'access content'));
    $this->drupalLogin($vote_user1);
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPost('node/' . $poll_nid, $edit, t('Vote'));
    $this->drupalLogout();

    $vote_user2 = $this->drupalCreateUser(array('vote on polls', 'access content'));
    $this->drupalLogin($vote_user2);
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPost('node/' . $poll_nid, $edit, t('Vote'));
    $this->drupalLogout();

    $vote_user3 = $this->drupalCreateUser(array('vote on polls', 'access content'));
    $this->drupalLogin($vote_user3);
    $edit = array(
      'choice' => '2',
    );
    $this->drupalPost('node/' . $poll_nid, $edit, t('Vote'));
    $this->drupalLogout();

    $vote_user4 = $this->drupalCreateUser(array('vote on polls', 'access content'));
    $this->drupalLogin($vote_user4);
    $edit = array(
      'choice' => '3',
    );
    $this->drupalPost('node/' . $poll_nid, $edit, t('Vote'));
    $this->drupalLogout();

    $poll = node_load($poll_nid, NULL, TRUE);

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[node:poll-votes]'] = 4;
    $tests['[node:poll-winner]'] = filter_xss($poll->choice[1]['chtext']);
    $tests['[node:poll-winner-votes]'] = 2;
    $tests['[node:poll-winner-percent]'] = 50;
    $tests['[node:poll-duration]'] = format_interval($poll->runtime, 1, $language_interface->langcode);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), t('No empty tokens generated.'));

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('node' => $poll), array('language' => $language_interface));
      $this->assertEqual($output, $expected, t('Sanitized poll token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[node:poll-winner]'] = $poll->choice[1]['chtext'];

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('node' => $poll), array('language' => $language_interface, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, t('Unsanitized poll token %token replaced.', array('%token' => $input)));
    }
  }
}
