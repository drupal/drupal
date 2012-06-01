<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollDeleteChoiceTest.
 */

namespace Drupal\poll\Tests;

class PollDeleteChoiceTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Poll choice deletion',
      'description' => 'Test the poll choice deletion logic.',
      'group' => 'Poll',
    );
  }

  function testChoiceRemoval() {
    // Set up a poll with three choices.
    $title = $this->randomName();
    $choices = array('First choice', 'Second choice', 'Third choice');
    $poll_nid = $this->pollCreate($title, $choices, FALSE);
    $this->assertTrue($poll_nid, t('Poll for choice deletion logic test created.'));

    // Edit the poll, and try to delete first poll choice.
    $this->drupalGet("node/$poll_nid/edit");
    $edit['choice[chid:1][chtext]'] = '';
    $this->drupalPost(NULL, $edit, t('Save'));

    // Click on the poll title to go to node page.
    $this->drupalGet('poll');
    $this->clickLink($title);

    // Check the first poll choice is deleted, while the others remain.
    $this->assertNoText('First choice', t('First choice removed.'));
    $this->assertText('Second choice', t('Second choice remains.'));
    $this->assertText('Third choice', t('Third choice remains.'));
  }
}
