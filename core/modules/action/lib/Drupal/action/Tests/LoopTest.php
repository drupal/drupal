<?php

/**
 * @file
 * Definition of Drupal\action\Tests\LoopTest.
 */

namespace Drupal\action\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests aborting of actions executing in a potential loop.
 */
class LoopTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog', 'action_loop_test');

  protected $aid;

  public static function getInfo() {
    return array(
      'name' => 'Actions executing in a potentially infinite loop',
      'description' => 'Tests actions executing in a loop, and makes sure they abort properly.',
      'group' => 'Action',
    );
  }

  /**
   * Sets up a loop with 3 - 12 recursions, and sees if it aborts properly.
   */
  function testActionLoop() {
    $user = $this->drupalCreateUser(array('administer actions'));
    $this->drupalLogin($user);

    $info = action_loop_test_action_info();
    $this->aid = action_save('action_loop_test_log', $info['action_loop_test_log']['type'], array(), $info['action_loop_test_log']['label']);

    // Delete any existing watchdog messages to clear the plethora of
    // "Action added" messages from when Drupal was installed.
    db_delete('watchdog')->execute();
    // To prevent this test from failing when xdebug is enabled, the maximum
    // recursion level should be kept low enough to prevent the xdebug
    // infinite recursion protection mechanism from aborting the request.
    // See http://drupal.org/node/587634.
    config('action.settings')
      ->set('recursion_limit', 7)
      ->save();
    $this->triggerActions();
  }

  /**
   * Loops watchdog messages up to actions_max_stack times.
   *
   * Creates an infinite loop by causing a watchdog message to be set,
   * which causes the actions to be triggered again, up to action_max_stack
   * times.
   */
  protected function triggerActions() {
    $this->drupalGet('<front>', array('query' => array('trigger_action_on_watchdog' => $this->aid)));
    $expected = array();
    $expected[] = 'Triggering action loop';
    $recursion_limit = config('action.settings')->get('recursion_limit');
    for ($i = 1; $i <= $recursion_limit; $i++) {
      $expected[] = "Test log #$i";
    }
    $expected[] = 'Stack overflow: recursion limit for actions_do() has been reached. Stack is limited by %limit calls.';

    $result = db_query("SELECT message FROM {watchdog} WHERE type = 'action_loop_test' OR type = 'action' ORDER BY wid");
    $loop_started = FALSE;
    foreach ($result as $row) {
      $expected_message = array_shift($expected);
      $this->assertEqual($row->message, $expected_message, format_string('Expected message %expected, got %message.', array('%expected' => $expected_message, '%message' => $row->message)));
    }
    $this->assertTrue(empty($expected), 'All expected messages found.');
  }
}
