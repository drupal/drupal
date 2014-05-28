<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Module\InstallUninstallTest.
 */

namespace Drupal\system\Tests\Module;

/**
 * Tests functionality for installing and uninstalling modules.
 */
class InstallUninstallTest extends ModuleTestBase {

  public static $modules = array('system_test', 'dblog');

  public static function getInfo() {
    return array(
      'name' => 'Install/uninstall modules',
      'description' => 'Install/uninstall core module and confirm table creation/deletion.',
      'group' => 'Module',
    );
  }

  /**
   * Tests that a fixed set of modules can be installed and uninstalled.
   */
  public function testInstallUninstall() {
    // Set a variable so that the hook implementations in system_test.module
    // will display messages via drupal_set_message().
    $this->container->get('state')->set('system_test.verbose_module_hooks', TRUE);

    // Install and uninstall module_test to ensure hook_preinstall_module and
    // hook_preuninstall_module are fired as expected.
    $this->container->get('module_handler')->install(array('module_test'));
    $this->assertEqual($this->container->get('state')->get('system_test_preinstall_module'), 'module_test');
    $this->container->get('module_handler')->uninstall(array('module_test'));
    $this->assertEqual($this->container->get('state')->get('system_test_preuninstall_module'), 'module_test');

    // Try to install and uninstall book, toolbar modules and its dependencies.
    $all_modules = system_rebuild_module_data();

    $all_modules = array_filter($all_modules, function ($module) {
      // Filter hidden, required and already enabled modules.
      if (!empty($module->info['hidden']) || !empty($module->info['required']) || $module->status == TRUE || $module->info['package'] == 'Testing') {
        return FALSE;
      }
      return TRUE;
    });

    // Go through each module in the list and try to install it (unless it was
    // already installed automatically due to a dependency).
    $automatically_installed = array();
    while (list($name, $module) = each($all_modules)) {
      // Skip modules that have been automatically installed.
      if (in_array($name, $automatically_installed)) {
        continue;
      }

      // Start a list of modules that we expect to be installed this time.
      $modules_to_install = array($name);
      foreach (array_keys($module->requires) as $dependency) {
        if (isset($all_modules[$dependency]) && !in_array($dependency, $automatically_installed)) {
          $modules_to_install[] = $dependency;

          // Add any potential dependency of this module to the list of modules we
          // expect to be automatically installed.
          $automatically_installed[] = $dependency;
        }
      }

      // Check that each module is not yet enabled and does not have any
      // database tables yet.
      foreach ($modules_to_install as $module_to_install) {
        $this->assertModules(array($module_to_install), FALSE);
        $this->assertModuleTablesDoNotExist($module_to_install);
      }

      // Install the module.
      $edit = array();
      $package = $module->info['package'];
      $edit["modules[$package][$name][enable]"] = TRUE;
      $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));

      // Handle the case where modules were installed along with this one and
      // where we therefore hit a confirmation screen.
      if (count($modules_to_install) > 1) {
        $this->drupalPostForm(NULL, array(), t('Continue'));
      }

      $this->assertText(t('The configuration options have been saved.'), 'Modules status has been updated.');

      // Check that hook_modules_installed() was invoked with the expected list
      // of modules, that each module's database tables now exist, and that
      // appropriate messages appear in the logs.
      foreach ($modules_to_install as $module_to_install) {
        $this->assertText(t('hook_modules_installed fired for @module', array('@module' => $module_to_install)));
        $this->assertModules(array($module_to_install), TRUE);
        $this->assertModuleTablesExist($module_to_install);
        $this->assertModuleConfig($module_to_install);
        $this->assertLogMessage('system', "%module module installed.", array('%module' => $module_to_install), WATCHDOG_INFO);
      }

      // Uninstall the original module, and check appropriate
      // hooks, tables, and log messages. (Later, we'll go back and do the
      // same thing for modules that were enabled automatically.)
      $this->assertSuccessfullUninstall($name, $package);
    }

    // Go through all modules that were automatically installed, and try to
    // uninstall them one by one.
    while ($automatically_installed) {
      $initial_count = count($automatically_installed);
      foreach ($automatically_installed as $name) {
        $package = $all_modules[$name]->info['package'];
        // If the module can't be uninstalled due to dependencies, skip it and
        // try again the next time. Otherwise, try to uninstall it.
        $this->drupalGet('admin/modules/uninstall');
        $disabled_checkbox = $this->xpath('//input[@type="checkbox" and @disabled="disabled" and @name="uninstall[' . $name . ']"]');
        if (empty($disabled_checkbox)) {
          $automatically_installed = array_diff($automatically_installed, array($name));
          $this->assertSuccessfullUninstall($name, $package);
        }
      }
      $final_count = count($automatically_installed);
      // If all checkboxes were disabled, something is really wrong with the
      // test. Throw a failure and avoid an infinite loop.
      if ($initial_count == $final_count) {
        $this->fail('Remaining modules could not be disabled.');
        break;
      }
    }

    // Now that all modules have been tested, go back and try to enable them
    // all again at once. This tests two things:
    // - That each module can be successfully enabled again after being
    //   uninstalled.
    // - That enabling more than one module at the same time does not lead to
    //   any errors.
    $edit = array();
    foreach ($all_modules as $name => $module) {
      $edit['modules[' . $module->info['package'] . '][' . $name . '][enable]'] = TRUE;
    }
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Modules status has been updated.');
  }

  /**
   * Uninstalls a module and asserts that it was done correctly.
   *
   * @param string $module
   *   The name of the module to uninstall.
   * @param string $package
   *   (optional) The package of the module to uninstall. Defaults
   *   to 'Core'.
   */
  protected function assertSuccessfullUninstall($module, $package = 'Core') {
    $edit = array();
    $edit['uninstall[' . $module . ']'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
    $this->assertModules(array($module), FALSE);

    // Check that the appropriate hook was fired and the appropriate log
    // message appears. (But don't check for the log message if the dblog
    // module was just uninstalled, since the {watchdog} table won't be there
    // anymore.)
    $this->assertText(t('hook_modules_uninstalled fired for @module', array('@module' => $module)));
    $this->assertLogMessage('system', "%module module uninstalled.", array('%module' => $module), WATCHDOG_INFO);

    // Check that the module's database tables no longer exist.
    $this->assertModuleTablesDoNotExist($module);
    // Check that the module's config files no longer exist.
    $this->assertNoModuleConfig($module);
  }

}
