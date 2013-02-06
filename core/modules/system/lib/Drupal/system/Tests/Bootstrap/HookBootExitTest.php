<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Bootstrap\HookExitTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\WebTestBase;

/**
 * Tests hook_exit().
 */
class HookExitTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system_test', 'dblog');

  public static function getInfo() {
    return array(
      'name' => 'Exit hook invocation',
      'description' => 'Test that hook_exit() is called correctly.',
      'group' => 'Bootstrap',
    );
  }

  /**
   * Tests calling of hook_exit().
   */
  function testHookExit() {
    // Test with cache disabled. Boot and exit should always fire.
    $config = config('system.performance');
    $config->set('cache.page.enabled', 0);
    $config->save();

    $this->drupalGet('');
    $calls = 1;
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, 'hook_exit called with disabled cache.');

    // Test with normal cache. Exit should be called.
    $config->set('cache.page.enabled', 1);
    $config->save();
    $this->drupalGet('');
    $calls++;
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, 'hook_exit called with normal cache.');

    // Exit should not fire since the page is cached.
    variable_set('page_cache_invoke_hooks', FALSE);
    $this->assertTrue(cache('page')->get(url('', array('absolute' => TRUE))), 'Page has been cached.');
    $this->drupalGet('');
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, 'hook_exit not called with aggressive cache and a cached page.');

    // Test with page cache cleared, exit should be called.
    $this->assertTrue(db_delete('cache_page')->execute(), 'Page cache cleared.');
    $this->drupalGet('');
    $calls++;
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, 'hook_exit called with aggressive cache and no cached page.');
  }
}
