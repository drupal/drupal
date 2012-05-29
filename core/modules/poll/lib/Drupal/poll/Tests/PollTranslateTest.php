<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollTranslateTest.
 */

namespace Drupal\poll\Tests;

/**
 * Tests poll translation logic.
 */
class PollTranslateTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Poll translation',
      'description' => 'Test the poll translation logic.',
      'group' => 'Poll',
    );
  }

  function setUp() {
    parent::setUp(array('translation'));
  }

  /**
   * Tests poll creation and translation.
   *
   * Checks that the choice names get copied from the original poll and that
   * the vote count values are set to 0.
   */
  function testPollTranslate() {
    $admin_user = $this->drupalCreateUser(array('administer content types', 'administer languages', 'edit any poll content', 'create poll content', 'administer nodes', 'translate content'));

    // Set up a poll with two choices.
    $title = $this->randomName();
    $choices = array($this->randomName(), $this->randomName());
    $poll_nid = $this->pollCreate($title, $choices, FALSE);
    $this->assertTrue($poll_nid, t('Poll for translation logic test created.'));

    $this->drupalLogout();
    $this->drupalLogin($admin_user);

    // Enable a second language.
    $this->drupalGet('admin/config/regional/language');
    $edit = array();
    $edit['predefined_langcode'] = 'nl';
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertRaw(t('The language %language has been created and can now be used.', array('%language' => 'Dutch')), t('Language Dutch has been created.'));

    // Set "Poll" content type to use multilingual support with translation.
    $this->drupalGet('admin/structure/types/manage/poll');
    $edit = array();
    $edit['node_type_language'] = TRANSLATION_ENABLED;
    $this->drupalPost('admin/structure/types/manage/poll', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Poll')), t('Poll content type has been updated.'));

    // Edit poll.
    $this->drupalGet("node/$poll_nid/edit");
    $edit = array();
    // Set the poll's first choice count to 200.
    $edit['choice[chid:1][chvotes]'] = 200;
    // Set the language to Dutch.
    $edit['langcode'] = 'nl';
    $this->drupalPost(NULL, $edit, t('Save'));

    // Translate the Dutch poll.
    $this->drupalGet('node/add/poll', array('query' => array('translation' => $poll_nid, 'target' => 'en')));

    $dutch_poll = node_load($poll_nid);

    // Check that the vote count values didn't get copied from the Dutch poll
    // and are set to 0.
    $this->assertFieldByName('choice[chid:1][chvotes]', '0', ('Found choice with vote count 0'));
    $this->assertFieldByName('choice[chid:2][chvotes]', '0', ('Found choice with vote count 0'));
    // Check that the choice names got copied from the Dutch poll.
    $this->assertFieldByName('choice[chid:1][chtext]', $dutch_poll->choice[1]['chtext'], t('Found choice with text @text', array('@text' => $dutch_poll->choice[1]['chtext'])));
    $this->assertFieldByName('choice[chid:2][chtext]', $dutch_poll->choice[2]['chtext'], t('Found choice with text @text', array('@text' => $dutch_poll->choice[2]['chtext'])));
  }
}
