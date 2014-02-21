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
   * The basic functionality of retrieving enabled modules.
   */
  function testModuleList() {
    // Build a list of modules, sorted alphabetically.
    $profile_info = install_profile_info('standard', 'en');
    $module_list = $profile_info['dependencies'];

    // Installation profile is a module that is expected to be loaded.
    $module_list[] = 'standard';

    sort($module_list);
    // Compare this list to the one returned by the extension handler. We expect
    // them to match, since all default profile modules have a weight equal to 0
    // (except for block.module, which has a lower weight but comes first in
    // the alphabet anyway).
    $this->assertModuleList($module_list, t('Standard profile'));

    // Try to install a new module.
    \Drupal::moduleHandler()->install(array('ban'));
    $module_list[] = 'ban';
    sort($module_list);
    $this->assertModuleList($module_list, t('After adding a module'));

    // Try to mess with the module weights.
    module_set_weight('ban', 20);

    // Move ban to the end of the array.
    unset($module_list[array_search('ban', $module_list)]);
    $module_list[] = 'ban';
    $this->assertModuleList($module_list, t('After changing weights'));

    // Test the fixed list feature.
    $fixed_list = array(
      'system' => 'core/modules/system/system.module',
      'menu' => 'core/modules/menu/menu.module',
    );
    $this->container->get('module_handler')->setModuleList($fixed_list);
    $new_module_list = array_combine(array_keys($fixed_list), array_keys($fixed_list));
    $this->assertModuleList($new_module_list, t('When using a fixed list'));

  }

  /**
   * Assert that the extension handler returns the expected values.
   *
   * @param $expected_values
   *   The expected values, sorted by weight and module name.
   */
  protected function assertModuleList(Array $expected_values, $condition) {
    $expected_values = array_values(array_unique($expected_values));
    $enabled_modules = array_keys($this->container->get('module_handler')->getModuleList());
    $enabled_modules = sort($enabled_modules);
    $this->assertEqual($expected_values, $enabled_modules, format_string('@condition: extension handler returns correct results', array('@condition' => $condition)));
  }

  /**
   * Test \Drupal::moduleHandler()->getImplementations() caching.
   */
  function testModuleImplements() {
    // Clear the cache.
    \Drupal::cache('bootstrap')->delete('module_implements');
    $this->assertFalse(\Drupal::cache('bootstrap')->get('module_implements'), 'The module implements cache is empty.');
    $this->drupalGet('');
    $this->assertTrue(\Drupal::cache('bootstrap')->get('module_implements'), 'The module implements cache is populated after requesting a page.');

    // Test again with an authenticated user.
    $this->user = $this->drupalCreateUser();
    $this->drupalLogin($this->user);
    \Drupal::cache('bootstrap')->delete('module_implements');
    $this->drupalGet('');
    $this->assertTrue(\Drupal::cache('bootstrap')->get('module_implements'), 'The module implements cache is populated after requesting a page.');

    // Prime ModuleHandler's hook implementation cache by invoking a random hook
    // name. The subsequent \Drupal\Core\Extension\ModuleHandler::install()
    // below will only call into setModuleList(), but will not explicitly reset
    // the hook implementation cache, as that is expected to happen implicitly
    // by setting the module list. This verifies that the hook implementation
    // cache is cleared whenever setModuleList() is called.
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('test');

    // Make sure group include files are detected properly even when the file is
    // already loaded when the cache is rebuilt.
    // For that activate the module_test which provides the file to load.
    \Drupal::moduleHandler()->install(array('module_test'));
    $module_handler->loadAll();
    module_load_include('inc', 'module_test', 'module_test.file');
    $modules = $module_handler->getImplementations('test_hook');
    $this->assertTrue(in_array('module_test', $modules), 'Hook found.');
  }

  /**
   * Test that moduleHandler()->invoke() can load a hook defined in
   * hook_hook_info().
   */
  function testModuleInvoke() {
    \Drupal::moduleHandler()->install(array('module_test'), FALSE);
    $this->resetAll();
    $this->drupalGet('module-test/hook-dynamic-loading-invoke');
    $this->assertText('success!', 'moduleHandler()->invoke() dynamically loads a hook defined in hook_hook_info().');
  }

  /**
   * Test that \Drupal::moduleHandler()->invokeAll() can load a hook defined in hook_hook_info().
   */
  function testModuleInvokeAll() {
    \Drupal::moduleHandler()->install(array('module_test'), FALSE);
    $this->resetAll();
    $this->drupalGet('module-test/hook-dynamic-loading-invoke-all');
    $this->assertText('success!', '\Drupal::moduleHandler()->invokeAll() dynamically loads a hook defined in hook_hook_info().');
  }

  /**
   * Test dependency resolution.
   */
  function testDependencyResolution() {
    // Enable the test module, and make sure that other modules we are testing
    // are not already enabled. (If they were, the tests below would not work
    // correctly.)
    \Drupal::moduleHandler()->install(array('module_test'), FALSE);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('module_test'), 'Test module is enabled.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('forum'), 'Forum module is disabled.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('ban'), 'Ban module is disabled.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('xmlrpc'), 'XML-RPC module is disabled.');

    // First, create a fake missing dependency. Forum depends on ban, which
    // depends on a made-up module, foo. Nothing should be installed.
    \Drupal::state()->set('module_test.dependency', 'missing dependency');
    drupal_static_reset('system_rebuild_module_data');
    $result = \Drupal::moduleHandler()->install(array('forum'));
    $this->assertFalse($result, '\Drupal\Core\Extension\ModuleHandler::install() returns FALSE if dependencies are missing.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('forum'), '\Drupal\Core\Extension\ModuleHandler::install() aborts if dependencies are missing.');

    // Now, fix the missing dependency. Forum module depends on ban, but ban
    // depends on the XML-RPC module.
    // \Drupal\Core\Extension\ModuleHandler::install() should work.
    \Drupal::state()->set('module_test.dependency', 'dependency');
    drupal_static_reset('system_rebuild_module_data');
    $result = \Drupal::moduleHandler()->install(array('forum'));
    $this->assertTrue($result, '\Drupal\Core\Extension\ModuleHandler::install() returns the correct value.');
    // Verify that the fake dependency chain was installed.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('ban') && \Drupal::moduleHandler()->moduleExists('xmlrpc'), 'Dependency chain was installed.');
    // Verify that the original module was installed.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('forum'), 'Module installation with unlisted dependencies succeeded.');
    // Finally, verify that the modules were enabled in the correct order.
    $module_order = \Drupal::state()->get('module_test.install_order') ?: array();
    $this->assertEqual($module_order, array('xmlrpc', 'ban', 'forum'), 'Modules were enabled in the correct order.');

    // Now uninstall all three modules explicitly, but in the incorrect order,
    // and make sure that drupal_uninstall_modules() uninstalled them in the
    // correct sequence.
    $result = module_uninstall(array('ban', 'xmlrpc', 'forum'));
    $this->assertTrue($result, 'module_uninstall() returns the correct value.');
    foreach (array('forum', 'ban', 'xmlrpc') as $module) {
      $this->assertEqual(drupal_get_installed_schema_version($module), SCHEMA_UNINSTALLED, format_string('The @module module was uninstalled.', array('@module' => $module)));
    }
    $uninstalled_modules = \Drupal::state()->get('module_test.uninstall_order') ?: array();
    $this->assertEqual($uninstalled_modules, array('forum', 'ban', 'xmlrpc'), 'Modules were uninstalled in the correct order by module_uninstall().');

    // Uninstall the profile module from above, and make sure that the profile
    // itself is not on the list of dependent modules to be uninstalled.
    $result = module_uninstall(array('comment'));
    $this->assertTrue($result, 'module_uninstall() returns the correct value.');
    $this->assertEqual(drupal_get_installed_schema_version('comment'), SCHEMA_UNINSTALLED, 'Comment module was uninstalled.');
    $uninstalled_modules = \Drupal::state()->get('module_test.uninstall_order') ?: array();
    $this->assertTrue(in_array('comment', $uninstalled_modules), 'Comment module is in the list of uninstalled modules.');
    $this->assertFalse(in_array($this->profile, $uninstalled_modules), 'The installation profile is not in the list of uninstalled modules.');

    // Enable forum module again, which should enable both the ban module and
    // XML-RPC module. But, this time do it with ban module declaring a
    // dependency on a specific version of XML-RPC module in its info file. Make
    // sure that Drupal\Core\Extension\ModuleHandler::install() still works.
    \Drupal::state()->set('module_test.dependency', 'version dependency');
    drupal_static_reset('system_rebuild_module_data');
    $result = \Drupal::moduleHandler()->install(array('forum'));
    $this->assertTrue($result, '\Drupal\Core\Extension\ModuleHandler::install() returns the correct value.');
    // Verify that the fake dependency chain was installed.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('ban') && \Drupal::moduleHandler()->moduleExists('xmlrpc'), 'Dependency chain was installed.');
    // Verify that the original module was installed.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('forum'), 'Module installation with version dependencies succeeded.');
    // Finally, verify that the modules were enabled in the correct order.
    $enable_order = \Drupal::state()->get('module_test.install_order') ?: array();
    $xmlrpc_position = array_search('xmlrpc', $enable_order);
    $ban_position = array_search('ban', $enable_order);
    $forum_position = array_search('forum', $enable_order);
    $xmlrpc_before_ban = $xmlrpc_position !== FALSE && $ban_position !== FALSE && $xmlrpc_position < $ban_position;
    $ban_before_forum = $ban_position !== FALSE && $forum_position !== FALSE && $ban_position < $forum_position;
    $this->assertTrue($xmlrpc_before_ban && $ban_before_forum, 'Modules were enabled in the correct order.');
  }

  /**
   * Tests whether the correct module metadata is returned.
   */
  function testModuleMetaData() {
    // Generate the list of available modules.
    $modules = system_rebuild_module_data();
    // Check that the mtime field exists for the system module.
    $this->assertTrue(!empty($modules['system']->info['mtime']), 'The system.info.yml file modification time field is present.');
    // Use 0 if mtime isn't present, to avoid an array index notice.
    $test_mtime = !empty($modules['system']->info['mtime']) ? $modules['system']->info['mtime'] : 0;
    // Ensure the mtime field contains a number that is greater than zero.
    $this->assertTrue(is_numeric($test_mtime) && ($test_mtime > 0), 'The system.info.yml file modification time field contains a timestamp.');
  }

  /**
   * Tests whether the correct theme metadata is returned.
   */
  function testThemeMetaData() {
    // Generate the list of available themes.
    $themes = system_rebuild_theme_data();
    // Check that the mtime field exists for the bartik theme.
    $this->assertTrue(!empty($themes['bartik']->info['mtime']), 'The bartik.info.yml file modification time field is present.');
    // Use 0 if mtime isn't present, to avoid an array index notice.
    $test_mtime = !empty($themes['bartik']->info['mtime']) ? $themes['bartik']->info['mtime'] : 0;
    // Ensure the mtime field contains a number that is greater than zero.
    $this->assertTrue(is_numeric($test_mtime) && ($test_mtime > 0), 'The bartik.info.yml file modification time field contains a timestamp.');
  }
}
