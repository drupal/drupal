<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Actions\LoopTest.
 */

namespace Drupal\system\Tests\Actions;

use Drupal\simpletest\WebTestBase;

/**
 * Test actions executing in a potential loop, and make sure they abort properly.
 */
class LoopTest extends WebTestBase {
  protected $aid;

  public static function getInfo() {
    return array(
      'name' => 'Actions executing in a potentially infinite loop',
      'description' => 'Tests actions executing in a loop, and makes sure they abort properly.',
      'group' => 'Actions',
    );
  }

  function setUp() {
    parent::setUp('dblog', 'actions_loop_test');
  }

  /**
   * Set up a loop with 3 - 12 recursions, and see if it aborts properly.
   */
  function testActionLoop() {
    $user = $this->drupalCreateUser(array('administer actions'));
    $this->drupalLogin($user);

    $info = actions_loop_test_action_info();
    $this->aid = actions_save('actions_loop_test_log', $info['actions_loop_test_log']['type'], array(), $info['actions_loop_test_log']['label']);

    // Delete any existing watchdog messages to clear the plethora of
    // "Action added" messages from when Drupal was installed.
    db_delete('watchdog')->execute();
    // To prevent this test from failing when xdebug is enabled, the maximum
    // recursion level should be kept low enough to prevent the xdebug
    // infinite recursion protection mechanism from aborting the request.
    // See http://drupal.org/node/587634.
    variable_set('actions_max_stack', 7);
    $this->triggerActions();
  }

  /**
   * Create an infinite loop by causing a watchdog message to be set,
   * which causes the actions to be triggered again, up to actions_max_stack
   * times.
   */
  protected function triggerActions() {
    $this->drupalGet('<front>', array('query' => array('trigger_actions_on_watchdog' => $this->aid)));
    $expected = array();
    $expected[] = 'Triggering action loop';
    for ($i = 1; $i <= variable_get('actions_max_stack', 35); $i++) {
      $expected[] = "Test log #$i";
    }
    $expected[] = 'Stack overflow: too many calls to actions_do(). Aborting to prevent infinite recursion.';

    $result = db_query("SELECT message FROM {watchdog} WHERE type = 'actions_loop_test' OR type = 'actions' ORDER BY wid");
    $loop_started = FALSE;
    foreach ($result as $row) {
      $expected_message = array_shift($expected);
      $this->assertEqual($row->message, $expected_message, t('Expected message %expected, got %message.', array('%expected' => $expected_message, '%message' => $row->message)));
    }
    $this->assertTrue(empty($expected), t('All expected messages found.'));
  }
}
