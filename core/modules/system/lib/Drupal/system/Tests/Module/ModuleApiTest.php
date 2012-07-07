<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\ModuleApiTest.
 */

namespace Drupal\system\Tests\Module;

use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for the module API.
 */
class ModuleApiTest extends WebTestBase {
  // Requires Standard profile modules/dependencies.
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Module API',
      'description' => 'Test low-level module functions.',
      'group' => 'Module',
    );
  }

  /**
   * The basic functionality of module_list().
   */
  function testModuleList() {
    // Build a list of modules, sorted alphabetically.
    $profile_info = install_profile_info('standard', 'en');
    $module_list = $profile_info['dependencies'];

    // Install profile is a module that is expected to be loaded.
    $module_list[] = 'standard';

    sort($module_list);
    // Compare this list to the one returned by module_list(). We expect them
    // to match, since all default profile modules have a weight equal to 0
    // (except for block.module, which has a lower weight but comes first in
    // the alphabet anyway).
    $this->assertModuleList($module_list, t('Standard profile'));

    // Try to install a new module.
    module_enable(array('contact'));
    $module_list[] = 'contact';
    sort($module_list);
    $this->assertModuleList($module_list, t('After adding a module'));

    // Try to mess with the module weights.
    db_update('system')
      ->fields(array('weight' => 20))
      ->condition('name', 'contact')
      ->condition('type', 'module')
      ->execute();
    // Reset the module list.
    system_list_reset();
    // Move contact to the end of the array.
    unset($module_list[array_search('contact', $module_list)]);
    $module_list[] = 'contact';
    $this->assertModuleList($module_list, t('After changing weights'));

    // Test the fixed list feature.
    $fixed_list = array(
      'system' => array('filename' => drupal_get_path('module', 'system')),
      'menu' => array('filename' => drupal_get_path('module', 'menu')),
    );
    module_list(NULL, $fixed_list);
    $new_module_list = array_combine(array_keys($fixed_list), array_keys($fixed_list));
    $this->assertModuleList($new_module_list, t('When using a fixed list'));

    // Reset the module list.
    module_list_reset();
    $this->assertModuleList($module_list, t('After reset'));
  }

  /**
   * Assert that module_list() return the expected values.
   *
   * @param $expected_values
   *   The expected values, sorted by weight and module name.
   */
  protected function assertModuleList(Array $expected_values, $condition) {
    $expected_values = array_combine($expected_values, $expected_values);
    $this->assertEqual($expected_values, module_list(), t('@condition: module_list() returns correct results', array('@condition' => $condition)));
  }

  /**
   * Test module_implements() caching.
   */
  function testModuleImplements() {
    // Clear the cache.
    cache('bootstrap')->delete('module_implements');
    $this->assertFalse(cache('bootstrap')->get('module_implements'), t('The module implements cache is empty.'));
    $this->drupalGet('');
    $this->assertTrue(cache('bootstrap')->get('module_implements'), t('The module implements cache is populated after requesting a page.'));

    // Test again with an authenticated user.
    $this->user = $this->drupalCreateUser();
    $this->drupalLogin($this->user);
    cache('bootstrap')->delete('module_implements');
    $this->drupalGet('');
    $this->assertTrue(cache('bootstrap')->get('module_implements'), t('The module implements cache is populated after requesting a page.'));

    // Make sure group include files are detected properly even when the file is
    // already loaded when the cache is rebuilt.
    // For that activate the module_test which provides the file to load.
    module_enable(array('module_test'));

    module_load_include('inc', 'module_test', 'module_test.file');
    $modules = module_implements('test_hook');
    $static = drupal_static('module_implements');
    $this->assertTrue(in_array('module_test', $modules), 'Hook found.');
    $this->assertEqual($static['test_hook']['module_test'], 'file', 'Include file detected.');
  }

  /**
   * Test that module_invoke() can load a hook defined in hook_hook_info().
   */
  function testModuleInvoke() {
    module_enable(array('module_test'), FALSE);
    $this->resetAll();
    $this->drupalGet('module-test/hook-dynamic-loading-invoke');
    $this->assertText('success!', t('module_invoke() dynamically loads a hook defined in hook_hook_info().'));
  }

  /**
   * Test that module_invoke_all() can load a hook defined in hook_hook_info().
   */
  function testModuleInvokeAll() {
    module_enable(array('module_test'), FALSE);
    $this->resetAll();
    $this->drupalGet('module-test/hook-dynamic-loading-invoke-all');
    $this->assertText('success!', t('module_invoke_all() dynamically loads a hook defined in hook_hook_info().'));
  }

  /**
   * Test that a menu item load function can invoke hooks defined in hook_hook_info().
   *
   * We test this separately from testModuleInvokeAll(), because menu item load
   * functions execute early in the request handling.
   */
  function testModuleInvokeAllDuringLoadFunction() {
    module_enable(array('module_test'), FALSE);
    $this->resetAll();
    $this->drupalGet('module-test/hook-dynamic-loading-invoke-all-during-load/module_test');
    $this->assertText('success!', t('Menu item load function invokes a hook defined in hook_hook_info().'));
  }

  /**
   * Test dependency resolution.
   */
  function testDependencyResolution() {
    // Enable the test module, and make sure that other modules we are testing
    // are not already enabled. (If they were, the tests below would not work
    // correctly.)
    module_enable(array('module_test'), FALSE);
    $this->assertTrue(module_exists('module_test'), t('Test module is enabled.'));
    $this->assertFalse(module_exists('forum'), t('Forum module is disabled.'));
    $this->assertFalse(module_exists('poll'), t('Poll module is disabled.'));
    $this->assertFalse(module_exists('php'), t('PHP module is disabled.'));

    // First, create a fake missing dependency. Forum depends on poll, which
    // depends on a made-up module, foo. Nothing should be installed.
    variable_set('dependency_test', 'missing dependency');
    drupal_static_reset('system_rebuild_module_data');
    $result = module_enable(array('forum'));
    $this->assertFalse($result, t('module_enable() returns FALSE if dependencies are missing.'));
    $this->assertFalse(module_exists('forum'), t('module_enable() aborts if dependencies are missing.'));

    // Now, fix the missing dependency. Forum module depends on poll, but poll
    // depends on the PHP module. module_enable() should work.
    variable_set('dependency_test', 'dependency');
    drupal_static_reset('system_rebuild_module_data');
    $result = module_enable(array('forum'));
    $this->assertTrue($result, t('module_enable() returns the correct value.'));
    // Verify that the fake dependency chain was installed.
    $this->assertTrue(module_exists('poll') && module_exists('php'), t('Dependency chain was installed by module_enable().'));
    // Verify that the original module was installed.
    $this->assertTrue(module_exists('forum'), t('Module installation with unlisted dependencies succeeded.'));
    // Finally, verify that the modules were enabled in the correct order.
    $this->assertEqual(variable_get('test_module_enable_order', array()), array('php', 'poll', 'forum'), t('Modules were enabled in the correct order by module_enable().'));

    // Now, disable the PHP module. Both forum and poll should be disabled as
    // well, in the correct order.
    module_disable(array('php'));
    $this->assertTrue(!module_exists('forum') && !module_exists('poll'), t('Depedency chain was disabled by module_disable().'));
    $this->assertFalse(module_exists('php'), t('Disabling a module with unlisted dependents succeeded.'));
    $this->assertEqual(variable_get('test_module_disable_order', array()), array('forum', 'poll', 'php'), t('Modules were disabled in the correct order by module_disable().'));

    // Disable a module that is listed as a dependency by the install profile.
    // Make sure that the profile itself is not on the list of dependent
    // modules to be disabled.
    $profile = drupal_get_profile();
    $info = install_profile_info($profile);
    $this->assertTrue(in_array('comment', $info['dependencies']), t('Comment module is listed as a dependency of the install profile.'));
    $this->assertTrue(module_exists('comment'), t('Comment module is enabled.'));
    module_disable(array('comment'));
    $this->assertFalse(module_exists('comment'), t('Comment module was disabled.'));
    $disabled_modules = variable_get('test_module_disable_order', array());
    $this->assertTrue(in_array('comment', $disabled_modules), t('Comment module is in the list of disabled modules.'));
    $this->assertFalse(in_array($profile, $disabled_modules), t('The installation profile is not in the list of disabled modules.'));

    // Try to uninstall the PHP module by itself. This should be rejected,
    // since the modules which it depends on need to be uninstalled first, and
    // that is too destructive to perform automatically.
    $result = module_uninstall(array('php'));
    $this->assertFalse($result, t('Calling module_uninstall() on a module whose dependents are not uninstalled fails.'));
    foreach (array('forum', 'poll', 'php') as $module) {
      $this->assertNotEqual(drupal_get_installed_schema_version($module), SCHEMA_UNINSTALLED, t('The @module module was not uninstalled.', array('@module' => $module)));
    }

    // Now uninstall all three modules explicitly, but in the incorrect order,
    // and make sure that drupal_uninstal_modules() uninstalled them in the
    // correct sequence.
    $result = module_uninstall(array('poll', 'php', 'forum'));
    $this->assertTrue($result, t('module_uninstall() returns the correct value.'));
    foreach (array('forum', 'poll', 'php') as $module) {
      $this->assertEqual(drupal_get_installed_schema_version($module), SCHEMA_UNINSTALLED, t('The @module module was uninstalled.', array('@module' => $module)));
    }
    $this->assertEqual(variable_get('test_module_uninstall_order', array()), array('forum', 'poll', 'php'), t('Modules were uninstalled in the correct order by module_uninstall().'));

    // Uninstall the profile module from above, and make sure that the profile
    // itself is not on the list of dependent modules to be uninstalled.
    $result = module_uninstall(array('comment'));
    $this->assertTrue($result, t('module_uninstall() returns the correct value.'));
    $this->assertEqual(drupal_get_installed_schema_version('comment'), SCHEMA_UNINSTALLED, t('Comment module was uninstalled.'));
    $uninstalled_modules = variable_get('test_module_uninstall_order', array());
    $this->assertTrue(in_array('comment', $uninstalled_modules), t('Comment module is in the list of uninstalled modules.'));
    $this->assertFalse(in_array($profile, $uninstalled_modules), t('The installation profile is not in the list of uninstalled modules.'));

    // Enable forum module again, which should enable both the poll module and
    // php module. But, this time do it with poll module declaring a dependency
    // on a specific version of php module in its info file. Make sure that
    // module_enable() still works.
    variable_set('dependency_test', 'version dependency');
    drupal_static_reset('system_rebuild_module_data');
    $result = module_enable(array('forum'));
    $this->assertTrue($result, t('module_enable() returns the correct value.'));
    // Verify that the fake dependency chain was installed.
    $this->assertTrue(module_exists('poll') && module_exists('php'), t('Dependency chain was installed by module_enable().'));
    // Verify that the original module was installed.
    $this->assertTrue(module_exists('forum'), t('Module installation with version dependencies succeeded.'));
    // Finally, verify that the modules were enabled in the correct order.
    $enable_order = variable_get('test_module_enable_order', array());
    $php_position = array_search('php', $enable_order);
    $poll_position = array_search('poll', $enable_order);
    $forum_position = array_search('forum', $enable_order);
    $php_before_poll = $php_position !== FALSE && $poll_position !== FALSE && $php_position < $poll_position;
    $poll_before_forum = $poll_position !== FALSE && $forum_position !== FALSE && $poll_position < $forum_position;
    $this->assertTrue($php_before_poll && $poll_before_forum, t('Modules were enabled in the correct order by module_enable().'));
  }

  /**
   * Tests whether the correct module metadata is returned.
   */
  function testModuleMetaData() {
    // Generate the list of available modules.
    $modules = system_rebuild_module_data();
    // Check that the mtime field exists for the system module.
    $this->assertTrue(!empty($modules['system']->info['mtime']), 'The system.info file modification time field is present.');
    // Use 0 if mtime isn't present, to avoid an array index notice.
    $test_mtime = !empty($modules['system']->info['mtime']) ? $modules['system']->info['mtime'] : 0;
    // Ensure the mtime field contains a number that is greater than zero.
    $this->assertTrue(is_numeric($test_mtime) && ($test_mtime > 0), 'The system.info file modification time field contains a timestamp.');
  }

  /**
   * Tests whether the correct theme metadata is returned.
   */
  function testThemeMetaData() {
    // Generate the list of available themes.
    $themes = system_rebuild_theme_data();
    // Check that the mtime field exists for the bartik theme.
    $this->assertTrue(!empty($themes['bartik']->info['mtime']), 'The bartik.info file modification time field is present.');
    // Use 0 if mtime isn't present, to avoid an array index notice.
    $test_mtime = !empty($themes['bartik']->info['mtime']) ? $themes['bartik']->info['mtime'] : 0;
    // Ensure the mtime field contains a number that is greater than zero.
    $this->assertTrue(is_numeric($test_mtime) && ($test_mtime > 0), 'The bartik.info file modification time field contains a timestamp.');
  }
}
