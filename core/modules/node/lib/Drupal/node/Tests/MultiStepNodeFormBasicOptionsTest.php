<?php

/**
 * @file
 * Definition of Drupal\node\Tests\MultiStepNodeFormBasicOptionsTest.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test multistep node forms basic options.
 */
class MultiStepNodeFormBasicOptionsTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Multistep node form basic options',
      'description' => 'Test the persistence of basic options through multiple steps.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp('poll');
    $web_user = $this->drupalCreateUser(array('administer nodes', 'create poll content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Change the default values of basic options to ensure they persist.
   */
  function testMultiStepNodeFormBasicOptions() {
    $edit = array(
      'title' => 'a',
      'status' => FALSE,
      'promote' => FALSE,
      'sticky' => 1,
      'choice[new:0][chtext]' => 'a',
      'choice[new:1][chtext]' => 'a',
    );
    $this->drupalPost('node/add/poll', $edit, t('Add another choice'));
    $this->assertNoFieldChecked('edit-status', 'status stayed unchecked');
    $this->assertNoFieldChecked('edit-promote', 'promote stayed unchecked');
    $this->assertFieldChecked('edit-sticky', 'sticky stayed checked');
  }
}
