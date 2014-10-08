<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Bootstrap\DrupalSetMessageTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\WebTestBase;

/**
 * Tests drupal_set_message() and related functions.
 *
 * @group Bootstrap
 */
class DrupalSetMessageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system_test');

  /**
   * Tests setting messages and removing one before it is displayed.
   */
  function testSetRemoveMessages() {
    // The page at system-test/drupal-set-message sets two messages and then
    // removes the first before it is displayed.
    $this->drupalGet('system-test/drupal-set-message');
    $this->assertNoText('First message (removed).');
    $this->assertText('Second message (not removed).');
  }

}
