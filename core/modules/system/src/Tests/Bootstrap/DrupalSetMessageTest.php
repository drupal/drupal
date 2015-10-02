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
   * Tests drupal_set_message().
   */
  function testDrupalSetMessage() {
    // The page at system-test/drupal-set-message sets two messages and then
    // removes the first before it is displayed.
    $this->drupalGet('system-test/drupal-set-message');
    $this->assertNoText('First message (removed).');
    $this->assertRaw(t('Second message with <em>markup!</em> (not removed).'));

    // Ensure duplicate messages are handled as expected.
    $this->assertUniqueText('Non Duplicated message');
    $this->assertNoUniqueText('Duplicated message');

    // Ensure Markup objects are rendered as expected.
    $this->assertRaw('Markup with <em>markup!</em>');
    $this->assertUniqueText('Markup with markup!');
    $this->assertRaw('Markup2 with <em>markup!</em>');

    // Ensure when the same message is of different types it is not duplicated.
    $this->assertUniqueText('Non duplicate Markup / string.');
    $this->assertNoUniqueText('Duplicate Markup / string.');

    // Ensure that strings that are not marked as safe are escaped.
    $this->assertEscaped('<em>This<span>markup will be</span> escaped</em>.');
  }

}
