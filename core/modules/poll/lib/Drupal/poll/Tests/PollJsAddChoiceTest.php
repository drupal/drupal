<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollJsAddChoiceTest.
 */

namespace Drupal\poll\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test adding new choices.
 */
class PollJsAddChoiceTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Poll add choice',
      'description' => 'Submits a POST request for an additional poll choice.',
      'group' => 'Poll'
    );
  }

  function setUp() {
    parent::setUp(array('poll'));
  }

  /**
   * Test adding a new choice.
   */
  function testAddChoice() {
    $web_user = $this->drupalCreateUser(array('create poll content', 'access content'));
    $this->drupalLogin($web_user);
    $this->drupalGet('node/add/poll');
    $edit = array(
      "title" => $this->randomName(),
      'choice[new:0][chtext]' => $this->randomName(),
      'choice[new:1][chtext]' => $this->randomName(),
    );

    // Press 'add choice' button through Ajax, and place the expected HTML result
    // as the tested content.
    $commands = $this->drupalPostAJAX(NULL, $edit, array('op' => t('Add another choice')));
    $this->content = $commands[1]['data'];

    $this->assertFieldByName('choice[chid:0][chtext]', $edit['choice[new:0][chtext]'], t('Field !i found', array('!i' => 0)));
    $this->assertFieldByName('choice[chid:1][chtext]', $edit['choice[new:1][chtext]'], t('Field !i found', array('!i' => 1)));
    $this->assertFieldByName('choice[new:0][chtext]', '', t('Field !i found', array('!i' => 2)));
  }
}
