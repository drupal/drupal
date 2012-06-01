<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollVoteCheckHostnameTest.
 */

namespace Drupal\poll\Tests;

class PollVoteCheckHostnameTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User poll vote capability.',
      'description' => 'Check that users and anonymous users from specified ip-address can only vote once.',
      'group' => 'Poll'
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user.
    $this->admin_user = $this->drupalCreateUser(array('administer permissions', 'create poll content'));
    $this->drupalLogin($this->admin_user);

    // Allow anonymous users to vote on polls.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access content' => TRUE,
      'vote on polls' => TRUE,
      'cancel own vote' => TRUE,
    ));

    // Enable page cache to verify that the result page is not saved in the
    // cache when anonymous voting is allowed.
    $config = config('system.performance');
    $config->set('cache', 1);
    $config->save();

    // Create poll.
    $title = $this->randomName();
    $choices = $this->_generateChoices(3);
    $this->poll_nid = $this->pollCreate($title, $choices, FALSE);

    $this->drupalLogout();

    // Create web users.
    $this->web_user1 = $this->drupalCreateUser(array('access content', 'vote on polls', 'cancel own vote'));
    $this->web_user2 = $this->drupalCreateUser(array('access content', 'vote on polls'));
  }

  /**
   * Check that anonymous users with same ip cannot vote on poll more than once
   * unless user is logged in.
   */
  function testHostnamePollVote() {
    // Login User1.
    $this->drupalLogin($this->web_user1);

    $edit = array(
      'choice' => '1',
    );

    // User1 vote on Poll.
    $this->drupalPost('node/' . $this->poll_nid, $edit, t('Vote'));
    $this->assertText(t('Your vote was recorded.'), t('%user vote was recorded.', array('%user' => $this->web_user1->name)));
    $this->assertText(t('Total votes: @votes', array('@votes' => 1)), t('Vote count updated correctly.'));

    // Check to make sure User1 cannot vote again.
    $this->drupalGet('node/' . $this->poll_nid);
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), t("%user is not able to vote again.", array('%user' => $this->web_user1->name)));
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(!empty($elements), t("'Cancel your vote' button appears."));

    // Logout User1.
    $this->drupalLogout();

    // Fill the page cache by requesting the poll.
    $this->drupalGet('node/' . $this->poll_nid);
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'MISS', t('Page was cacheable but was not in the cache.'));
    $this->drupalGet('node/' . $this->poll_nid);
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'HIT', t('Page was cached.'));

    // Anonymous user vote on Poll.
    $this->drupalPost(NULL, $edit, t('Vote'));
    $this->assertText(t('Your vote was recorded.'), t('Anonymous vote was recorded.'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 2)), t('Vote count updated correctly.'));
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(!empty($elements), t("'Cancel your vote' button appears."));

    // Check to make sure Anonymous user cannot vote again.
    $this->drupalGet('node/' . $this->poll_nid);
    $this->assertFalse($this->drupalGetHeader('x-drupal-cache'), t('Page was not cacheable.'));
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), t("Anonymous is not able to vote again."));
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(!empty($elements), t("'Cancel your vote' button appears."));

    // Login User2.
    $this->drupalLogin($this->web_user2);

    // User2 vote on poll.
    $this->drupalPost('node/' . $this->poll_nid, $edit, t('Vote'));
    $this->assertText(t('Your vote was recorded.'), t('%user vote was recorded.', array('%user' => $this->web_user2->name)));
    $this->assertText(t('Total votes: @votes', array('@votes' => 3)), 'Vote count updated correctly.');
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(empty($elements), t("'Cancel your vote' button does not appear."));

    // Logout User2.
    $this->drupalLogout();

    // Change host name for anonymous users.
    db_update('poll_vote')
      ->fields(array(
        'hostname' => '123.456.789.1',
      ))
      ->condition('hostname', '', '<>')
      ->execute();

    // Check to make sure Anonymous user can vote again with a new session after
    // a hostname change.
    $this->drupalGet('node/' . $this->poll_nid);
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'MISS', t('Page was cacheable but was not in the cache.'));
    $this->drupalPost(NULL, $edit, t('Vote'));
    $this->assertText(t('Your vote was recorded.'), t('%user vote was recorded.', array('%user' => $this->web_user2->name)));
    $this->assertText(t('Total votes: @votes', array('@votes' => 4)), 'Vote count updated correctly.');
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(!empty($elements), t("'Cancel your vote' button appears."));

    // Check to make sure Anonymous user cannot vote again with a new session,
    // and that the vote from the previous session cannot be cancelledd.
    $this->curlClose();
    $this->drupalGet('node/' . $this->poll_nid);
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'MISS', t('Page was cacheable but was not in the cache.'));
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), t('Anonymous is not able to vote again.'));
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(empty($elements), t("'Cancel your vote' button does not appear."));

    // Login User1.
    $this->drupalLogin($this->web_user1);

    // Check to make sure User1 still cannot vote even after hostname changed.
    $this->drupalGet('node/' . $this->poll_nid);
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), t("%user is not able to vote again.", array('%user' => $this->web_user1->name)));
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(!empty($elements), t("'Cancel your vote' button appears."));
  }
}
