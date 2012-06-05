<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\HookBootExitTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\WebTestBase;

/**
 * Tests hook_boot() and hook_exit().
 */
class HookBootExitTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Boot and exit hook invocation',
      'description' => 'Test that hook_boot() and hook_exit() are called correctly.',
      'group' => 'Bootstrap',
    );
  }

  function setUp() {
    parent::setUp('system_test', 'dblog');
  }

  /**
   * Test calling of hook_boot() and hook_exit().
   */
  function testHookBootExit() {
    // Test with cache disabled. Boot and exit should always fire.
    $config = config('system.performance');
    $config->set('cache', 0);
    $config->save();

    $this->drupalGet('');
    $calls = 1;
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_boot'))->fetchField(), $calls, t('hook_boot called with disabled cache.'));
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, t('hook_exit called with disabled cache.'));

    // Test with normal cache. Boot and exit should be called.
    $config->set('cache', 1);
    $config->save();
    $this->drupalGet('');
    $calls++;
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_boot'))->fetchField(), $calls, t('hook_boot called with normal cache.'));
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, t('hook_exit called with normal cache.'));

    // Boot and exit should not fire since the page is cached.
    variable_set('page_cache_invoke_hooks', FALSE);
    $this->assertTrue(cache('page')->get(url('', array('absolute' => TRUE))), t('Page has been cached.'));
    $this->drupalGet('');
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_boot'))->fetchField(), $calls, t('hook_boot not called with aggressive cache and a cached page.'));
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, t('hook_exit not called with aggressive cache and a cached page.'));

    // Test with page cache cleared, boot and exit should be called.
    $this->assertTrue(db_delete('cache_page')->execute(), t('Page cache cleared.'));
    $this->drupalGet('');
    $calls++;
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_boot'))->fetchField(), $calls, t('hook_boot called with aggressive cache and no cached page.'));
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {watchdog} WHERE type = :type AND message = :message', array(':type' => 'system_test', ':message' => 'hook_exit'))->fetchField(), $calls, t('hook_exit called with aggressive cache and no cached page.'));
  }
}
