<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\ShutdownFunctionsTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests shutdown functions.
 */
class ShutdownFunctionsTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Shutdown functions',
      'description' => 'Functional tests for shutdown functions',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp('system_test');
  }

  /**
   * Test shutdown functions.
   */
  function testShutdownFunctions() {
    $arg1 = $this->randomName();
    $arg2 = $this->randomName();
    $this->drupalGet('system-test/shutdown-functions/' . $arg1 . '/' . $arg2);
    $this->assertText(t('First shutdown function, arg1 : @arg1, arg2: @arg2', array('@arg1' => $arg1, '@arg2' => $arg2)));
    $this->assertText(t('Second shutdown function, arg1 : @arg1, arg2: @arg2', array('@arg1' => $arg1, '@arg2' => $arg2)));

    // Make sure exceptions displayed through _drupal_render_exception_safe()
    // are correctly escaped.
    $this->assertRaw('Drupal is &amp;lt;blink&amp;gt;awesome&amp;lt;/blink&amp;gt;.');
  }
}
