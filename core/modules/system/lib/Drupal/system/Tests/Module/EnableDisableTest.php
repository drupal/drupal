<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\EnableDisableTest.
 */

namespace Drupal\system\Tests\Module;

/**
 * Test module enabling/disabling functionality.
 */
class EnableDisableTest extends ModuleTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Enable/disable modules',
      'description' => 'Enable/disable core module and confirm table creation/deletion.',
      'group' => 'Module',
    );
  }

  /**
   * Test that all core modules can be enabled, disabled and uninstalled.
   */
  function testEnableDisable() {
    // Try to enable, disable and uninstall all core modules, unless they are
    // hidden or required.
    $modules = system_rebuild_module_data();
    foreach ($modules as $name => $module) {
      if ($module->info['package'] != 'Core' || !empty($module->info['hidden']) || !empty($module->info['required'])) {
        unset($modules[$name]);
      }
    }

    // Throughout this test, some modules may be automatically enabled (due to
    // dependencies). We'll keep track of them in an array, so we can handle
    // them separately.
    $automatically_enabled = array();

    // Remove already enabled modules (via installation profile).
    // @todo Remove this after removing all dependencies from Testing profile.
    foreach (module_list() as $dependency) {
      // Exclude required modules. Only installation profile "suggestions" can
      // be disabled and uninstalled.
      if (isset($modules[$dependency])) {
        $automatically_enabled[$dependency] = TRUE;
      }
    }

    $this->assertTrue(count($modules), t('Found @count modules that can be enabled: %modules', array(
      '@count' => count($modules),
      '%modules' => implode(', ', array_keys($modules)),
    )));

    // Enable the dblog module first, since we will be asserting the presence
    // of log messages throughout the test.
   if (isset($modules['dblog'])) {
     $modules = array('dblog' => $modules['dblog']) + $modules;
   }

   // Set a variable so that the hook implementations in system_test.module
   // will display messages via drupal_set_message().
   variable_set('test_verbose_module_hooks', TRUE);

    // Go through each module in the list and try to enable it (unless it was
    // already enabled automatically due to a dependency).
    foreach ($modules as $name => $module) {
      if (empty($automatically_enabled[$name])) {
        // Start a list of modules that we expect to be enabled this time.
        $modules_to_enable = array($name);

        // Find out if the module has any dependencies that aren't enabled yet;
        // if so, add them to the list of modules we expect to be automatically
        // enabled.
        foreach (array_keys($module->requires) as $dependency) {
          if (isset($modules[$dependency]) && empty($automatically_enabled[$dependency])) {
            $modules_to_enable[] = $dependency;
            $automatically_enabled[$dependency] = TRUE;
          }
        }

        // Check that each module is not yet enabled and does not have any
        // database tables yet.
        foreach ($modules_to_enable as $module_to_enable) {
          $this->assertModules(array($module_to_enable), FALSE);
          $this->assertModuleTablesDoNotExist($module_to_enable);
        }

        // Install and enable the module.
        $edit = array();
        $edit['modules[Core][' . $name . '][enable]'] = $name;
        $this->drupalPost('admin/modules', $edit, t('Save configuration'));
        // Handle the case where modules were installed along with this one and
        // where we therefore hit a confirmation screen.
        if (count($modules_to_enable) > 1) {
          $this->drupalPost(NULL, array(), t('Continue'));
        }
        $this->assertText(t('The configuration options have been saved.'), t('Modules status has been updated.'));

        // Check that hook_modules_installed() and hook_modules_enabled() were
        // invoked with the expected list of modules, that each module's
        // database tables now exist, and that appropriate messages appear in
        // the logs.
        foreach ($modules_to_enable as $module_to_enable) {
          $this->assertText(t('hook_modules_installed fired for @module', array('@module' => $module_to_enable)));
          $this->assertText(t('hook_modules_enabled fired for @module', array('@module' => $module_to_enable)));
          $this->assertModules(array($module_to_enable), TRUE);
          $this->assertModuleTablesExist($module_to_enable);
          $this->assertModuleConfig($module_to_enable);
          $this->assertLogMessage('system', "%module module installed.", array('%module' => $module_to_enable), WATCHDOG_INFO);
          $this->assertLogMessage('system', "%module module enabled.", array('%module' => $module_to_enable), WATCHDOG_INFO);
        }

        // Disable and uninstall the original module, and check appropriate
        // hooks, tables, and log messages. (Later, we'll go back and do the
        // same thing for modules that were enabled automatically.) Skip this
        // for the dblog module, because that is needed for the test; we'll go
        // back and do that one at the end also.
        if ($name != 'dblog') {
          $this->assertSuccessfulDisableAndUninstall($name);
        }
      }
    }

    // Go through all modules that were automatically enabled, and try to
    // disable and uninstall them one by one.
    while (!empty($automatically_enabled)) {
      $initial_count = count($automatically_enabled);
      foreach (array_keys($automatically_enabled) as $name) {
        // If the module can't be disabled due to dependencies, skip it and try
        // again the next time. Otherwise, try to disable it.
        $this->drupalGet('admin/modules');
        $disabled_checkbox = $this->xpath('//input[@type="checkbox" and @disabled="disabled" and @name="modules[Core][' . $name . '][enable]"]');
        if (empty($disabled_checkbox) && $name != 'dblog') {
          unset($automatically_enabled[$name]);
          $this->assertSuccessfulDisableAndUninstall($name);
        }
      }
      $final_count = count($automatically_enabled);
      // If all checkboxes were disabled, something is really wrong with the
      // test. Throw a failure and avoid an infinite loop.
      if ($initial_count == $final_count) {
        $this->fail(t('Remaining modules could not be disabled.'));
        break;
      }
    }

    // Disable and uninstall the dblog module last, since we needed it for
    // assertions in all the above tests.
    if (isset($modules['dblog'])) {
      $this->assertSuccessfulDisableAndUninstall('dblog');
    }

    // Now that all modules have been tested, go back and try to enable them
    // all again at once. This tests two things:
    // - That each module can be successfully enabled again after being
    //   uninstalled.
    // - That enabling more than one module at the same time does not lead to
    //   any errors.
    $edit = array();
    foreach (array_keys($modules) as $name) {
      $edit['modules[Core][' . $name . '][enable]'] = $name;
    }
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Modules status has been updated.'));
  }

  /**
   * Disables and uninstalls a module and asserts that it was done correctly.
   *
   * @param $module
   *   The name of the module to disable and uninstall.
   */
  function assertSuccessfulDisableAndUninstall($module) {
    // Disable the module.
    $edit = array();
    $edit['modules[Core][' . $module . '][enable]'] = FALSE;
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Modules status has been updated.'));
    $this->assertModules(array($module), FALSE);

    // Check that the appropriate hook was fired and the appropriate log
    // message appears.
    $this->assertText(t('hook_modules_disabled fired for @module', array('@module' => $module)));
    $this->assertLogMessage('system', "%module module disabled.", array('%module' => $module), WATCHDOG_INFO);

    //  Check that the module's database tables still exist.
    $this->assertModuleTablesExist($module);
    //  Check that the module's config files still exist.
    $this->assertModuleConfig($module);

    // Uninstall the module.
    $edit = array();
    $edit['uninstall[' . $module . ']'] = $module;
    $this->drupalPost('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPost(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), t('Modules status has been updated.'));
    $this->assertModules(array($module), FALSE);

    // Check that the appropriate hook was fired and the appropriate log
    // message appears. (But don't check for the log message if the dblog
    // module was just uninstalled, since the {watchdog} table won't be there
    // anymore.)
    $this->assertText(t('hook_modules_uninstalled fired for @module', array('@module' => $module)));
    if ($module != 'dblog') {
      $this->assertLogMessage('system', "%module module uninstalled.", array('%module' => $module), WATCHDOG_INFO);
    }

    // Check that the module's database tables no longer exist.
    $this->assertModuleTablesDoNotExist($module);
    // Check that the module's config files no longer exist.
    $this->assertNoModuleConfig($module);
  }
}
