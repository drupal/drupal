<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollCreateTest.
 */

namespace Drupal\poll\Tests;

class PollCreateTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Poll create',
      'description' => 'Adds "more choices", previews and creates a poll.',
      'group' => 'Poll'
    );
  }

  function testPollCreate() {
    $title = $this->randomName();
    $choices = $this->_generateChoices(7);
    $poll_nid = $this->pollCreate($title, $choices, TRUE);

    // Verify poll appears on 'poll' page.
    $this->drupalGet('poll');
    $this->assertText($title, 'Poll appears in poll list.');
    $this->assertText('open', 'Poll is active.');

    // Click on the poll title to go to node page.
    $this->clickLink($title);
    $this->assertText('Total votes: 0', 'Link to poll correct.');

    // Now add a new option to make sure that when we update the node the
    // option is displayed.
    $node = node_load($poll_nid);

    $new_option = $this->randomName();

    $vote_count = '2000';
    $node->choice[] = array(
      'chid' => '',
      'chtext' => $new_option,
      'chvotes' => (int) $vote_count,
      'weight' => 1000,
    );

    $node->save();

    $this->drupalGet('poll');
    $this->clickLink($title);
    $this->assertText($new_option, 'New option found.');

    $option = $this->xpath('//article[@id="node-1"]//div[@class="poll"]//dt[@class="choice-title"]');
    $this->assertEqual(end($option), $new_option, 'Last item is equal to new option.');

    $votes = $this->xpath('//article[@id="node-1"]//div[@class="poll"]//div[@class="percent"]');
    $this->assertTrue(strpos(end($votes), $vote_count) > 0, t("Votes saved."));
  }

  function testPollClose() {
    $content_user = $this->drupalCreateUser(array('create poll content', 'edit any poll content', 'access content'));
    $vote_user = $this->drupalCreateUser(array('cancel own vote', 'inspect all votes', 'vote on polls', 'access content'));

    // Create poll.
    $title = $this->randomName();
    $choices = $this->_generateChoices(7);
    $poll_nid = $this->pollCreate($title, $choices, FALSE);

    $this->drupalLogout();
    $this->drupalLogin($content_user);

    // Edit the poll node and close the poll.
    $close_edit = array('active' => 0);
    $this->pollUpdate($poll_nid, $title, $close_edit);

    // Verify 'Vote' button no longer appears.
    $this->drupalGet('node/' . $poll_nid);
    $elements = $this->xpath('//input[@id="edit-vote"]');
    $this->assertTrue(empty($elements), t("Vote button doesn't appear."));

    // Verify status on 'poll' page is 'closed'.
    $this->drupalGet('poll');
    $this->assertText($title, 'Poll appears in poll list.');
    $this->assertText('closed', 'Poll is closed.');

    // Edit the poll node and re-activate.
    $open_edit = array('active' => 1);
    $this->pollUpdate($poll_nid, $title, $open_edit);

    // Vote on the poll.
    $this->drupalLogout();
    $this->drupalLogin($vote_user);
    $vote_edit = array('choice' => '1');
    $this->drupalPost('node/' . $poll_nid, $vote_edit, t('Vote'));
    $this->assertText('Your vote was recorded.', 'Your vote was recorded.');
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(isset($elements[0]), t("'Cancel your vote' button appears."));

    // Edit the poll node and close the poll.
    $this->drupalLogout();
    $this->drupalLogin($content_user);
    $close_edit = array('active' => 0);
    $this->pollUpdate($poll_nid, $title, $close_edit);

    // Verify 'Cancel your vote' button no longer appears.
    $this->drupalGet('node/' . $poll_nid);
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(empty($elements), t("'Cancel your vote' button no longer appears."));
  }
}
