<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\workspaces\Entity\Workspace;

/**
 * Install/uninstall core module and confirm table creation/deletion.
 *
 * @group #slow
 * @group Module
 */
class InstallUninstallTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system_test',
    'dblog',
    'taxonomy',
    'update_test_postupdate',
  ];

  /**
   * Tests that a fixed set of modules can be installed and uninstalled.
   */
  public function testInstallUninstall() {
    // Set a variable so that the hook implementations in system_test.module
    // will display messages via
    // \Drupal\Core\Messenger\MessengerInterface::addStatus().
    $this->container->get('state')->set('system_test.verbose_module_hooks', TRUE);

    // Install and uninstall module_test to ensure hook_preinstall_module and
    // hook_preuninstall_module are fired as expected.
    $this->container->get('module_installer')->install(['module_test']);
    $this->assertEqual($this->container->get('state')->get('system_test_preinstall_module'), 'module_test');
    $this->container->get('module_installer')->uninstall(['module_test']);
    $this->assertEqual($this->container->get('state')->get('system_test_preuninstall_module'), 'module_test');
    $this->resetAll();

    $all_modules = $this->container->get('extension.list.module')->getList();

    // Test help on required modules, but do not test uninstalling.
    $required_modules = array_filter($all_modules, function ($module) {
      if (!empty($module->info['required']) || $module->status == TRUE) {
        if ($module->info['package'] != 'Testing' && empty($module->info['hidden'])) {
          return TRUE;
        }
      }
      return FALSE;
    });

    $required_modules['help'] = $all_modules['help'];

    // Test uninstalling without hidden, required, and already enabled modules.
    $all_modules = array_filter($all_modules, function ($module) {
      if (!empty($module->info['hidden']) || !empty($module->info['required']) || $module->status == TRUE || $module->info['package'] == 'Testing') {
        return FALSE;
      }
      return TRUE;
    });

    // Install the Help module, and verify it installed successfully.
    unset($all_modules['help']);
    $this->assertModuleNotInstalled('help');
    $edit = [];
    $edit["modules[help][enable]"] = TRUE;
    $this->drupalPostForm('admin/modules', $edit, t('Install'));
    $this->assertText('has been enabled', 'Modules status has been updated.');
    $this->assertText(t('hook_modules_installed fired for help'));
    $this->assertModuleSuccessfullyInstalled('help');

    // Test help for the required modules.
    foreach ($required_modules as $name => $module) {
      $this->assertHelp($name, $module->info['name']);
    }

    // Go through each module in the list and try to install and uninstall
    // it with its dependencies.
    foreach ($all_modules as $name => $module) {
      $was_installed_list = \Drupal::moduleHandler()->getModuleList();

      // Start a list of modules that we expect to be installed this time.
      $modules_to_install = [$name];
      foreach (array_keys($module->requires) as $dependency) {
        if (isset($all_modules[$dependency])) {
          $modules_to_install[] = $dependency;
        }
      }

      // Check that each module is not yet enabled and does not have any
      // database tables yet.
      foreach ($modules_to_install as $module_to_install) {
        $this->assertModuleNotInstalled($module_to_install);
      }

      // Install the module.
      $edit = [];
      $package = $module->info['package'];
      $edit['modules[' . $name . '][enable]'] = TRUE;
      $this->drupalPostForm('admin/modules', $edit, t('Install'));

      // Handle experimental modules, which require a confirmation screen.
      if ($package == 'Core (Experimental)') {
        $this->assertText('Are you sure you wish to enable experimental modules?');
        if (count($modules_to_install) > 1) {
          // When there are experimental modules, needed dependencies do not
          // result in the same page title, but there will be expected text
          // indicating they need to be enabled.
          $this->assertText('You must enable');
        }
        $this->drupalPostForm(NULL, [], t('Continue'));
      }
      // Handle the case where modules were installed along with this one and
      // where we therefore hit a confirmation screen.
      elseif (count($modules_to_install) > 1) {
        // Verify that we are on the correct form and that the expected text
        // about enabling dependencies appears.
        $this->assertText('Some required modules must be enabled');
        $this->assertText('You must enable');
        $this->drupalPostForm(NULL, [], t('Continue'));
      }

      // List the module display names to check the confirmation message.
      $module_names = [];
      foreach ($modules_to_install as $module_to_install) {
        $module_names[] = $all_modules[$module_to_install]->info['name'];
      }
      $expected_text = \Drupal::translation()->formatPlural(count($module_names), 'Module @name has been enabled.', '@count modules have been enabled: @names.', [
        '@name' => $module_names[0],
        '@names' => implode(', ', $module_names),
      ]);
      $this->assertText($expected_text, 'Modules status has been updated.');

      // Check that hook_modules_installed() was invoked with the expected list
      // of modules, that each module's database tables now exist, and that
      // appropriate messages appear in the logs.
      foreach ($modules_to_install as $module_to_install) {
        $this->assertText(t('hook_modules_installed fired for @module', ['@module' => $module_to_install]));
        $this->assertLogMessage('system', "%module module installed.", ['%module' => $module_to_install], RfcLogLevel::INFO);
        $this->assertInstallModuleUpdates($module_to_install);
        $this->assertModuleSuccessfullyInstalled($module_to_install);
      }

      // Verify the help page.
      $this->assertHelp($name, $module->info['name']);

      // Uninstall the original module, plus everything else that was installed
      // with it.
      if ($name == 'forum') {
        // Forum has an extra step to be able to uninstall it.
        $this->preUninstallForum();
      }

      // Delete all workspaces before uninstall.
      if ($name == 'workspaces') {
        $workspaces = Workspace::loadMultiple();
        \Drupal::entityTypeManager()->getStorage('workspace')->delete($workspaces);
      }

      $now_installed_list = \Drupal::moduleHandler()->getModuleList();
      $added_modules = array_diff(array_keys($now_installed_list), array_keys($was_installed_list));
      while ($added_modules) {
        $initial_count = count($added_modules);
        foreach ($added_modules as $to_uninstall) {
          // See if we can currently uninstall this module (if its dependencies
          // have been uninstalled), and do so if we can.
          $this->drupalGet('admin/modules/uninstall');
          $field_name = "uninstall[$to_uninstall]";
          $has_checkbox = $this->xpath('//input[@type="checkbox" and @name="' . $field_name . '"]');
          $disabled = $this->xpath('//input[@type="checkbox" and @name="' . $field_name . '" and @disabled="disabled"]');

          if (!empty($has_checkbox) && empty($disabled)) {
            // This one is eligible for being uninstalled.
            $package = $all_modules[$to_uninstall]->info['package'];
            $this->assertSuccessfulUninstall($to_uninstall, $package);
            $added_modules = array_diff($added_modules, [$to_uninstall]);
          }
        }

        // If we were not able to find a module to uninstall, fail and exit the
        // loop.
        $final_count = count($added_modules);
        if ($initial_count == $final_count) {
          $this->fail('Remaining modules could not be uninstalled for ' . $name);
          break;
        }
      }
    }

    // Uninstall the help module and put it back into the list of modules.
    $all_modules['help'] = $required_modules['help'];
    $this->assertSuccessfulUninstall('help', $required_modules['help']->info['package']);

    // Now that all modules have been tested, go back and try to enable them
    // all again at once. This tests two things:
    // - That each module can be successfully enabled again after being
    //   uninstalled.
    // - That enabling more than one module at the same time does not lead to
    //   any errors.
    $edit = [];
    $experimental = FALSE;
    foreach ($all_modules as $name => $module) {
      $edit['modules[' . $name . '][enable]'] = TRUE;
      // Track whether there is at least one experimental module.
      if ($module->info['package'] == 'Core (Experimental)') {
        $experimental = TRUE;
      }
    }
    $this->drupalPostForm('admin/modules', $edit, t('Install'));

    // If there are experimental modules, click the confirm form.
    if ($experimental) {
      $this->assertText('Are you sure you wish to enable experimental modules?');
      $this->drupalPostForm(NULL, [], t('Continue'));
    }
    // The string tested here is translatable but we are only using a part of it
    // so using a translated string is wrong. Doing so would create a new string
    // to translate.
    $this->assertText(new FormattableMarkup('@count modules have been enabled: ', ['@count' => count($all_modules)]), 'Modules status has been updated.');
  }

  /**
   * Asserts that a module is not yet installed.
   *
   * @param string $name
   *   Name of the module to check.
   */
  protected function assertModuleNotInstalled($name) {
    $this->assertModules([$name], FALSE);
    $this->assertModuleTablesDoNotExist($name);
  }

  /**
   * Asserts that a module was successfully installed.
   *
   * @param string $name
   *   Name of the module to check.
   */
  protected function assertModuleSuccessfullyInstalled($name) {
    $this->assertModules([$name], TRUE);
    $this->assertModuleTablesExist($name);
    $this->assertModuleConfig($name);
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
  protected function assertSuccessfulUninstall($module, $package = 'Core') {
    $edit = [];
    $edit['uninstall[' . $module . ']'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
    $this->assertModules([$module], FALSE);

    // Check that the appropriate hook was fired and the appropriate log
    // message appears. (But don't check for the log message if the dblog
    // module was just uninstalled, since the {watchdog} table won't be there
    // anymore.)
    $this->assertText(t('hook_modules_uninstalled fired for @module', ['@module' => $module]));
    $this->assertLogMessage('system', "%module module uninstalled.", ['%module' => $module], RfcLogLevel::INFO);

    // Check that the module's database tables no longer exist.
    $this->assertModuleTablesDoNotExist($module);
    // Check that the module's config files no longer exist.
    $this->assertNoModuleConfig($module);
    $this->assertUninstallModuleUpdates($module);
  }

  /**
   * Asserts the module post update functions after install.
   *
   * @param string $module
   *   The module that got installed.
   */
  protected function assertInstallModuleUpdates($module) {
    /** @var \Drupal\Core\Update\UpdateRegistry $post_update_registry */
    $post_update_registry = \Drupal::service('update.post_update_registry');
    $all_update_functions = $post_update_registry->getPendingUpdateFunctions();
    $empty_result = TRUE;
    foreach ($all_update_functions as $function) {
      list($function_module,) = explode('_post_update_', $function);
      if ($module === $function_module) {
        $empty_result = FALSE;
        break;
      }
    }
    $this->assertTrue($empty_result, 'Ensures that no pending post update functions are available.');

    $existing_updates = \Drupal::keyValue('post_update')->get('existing_updates', []);
    switch ($module) {
      case 'update_test_postupdate':
        $expected = [
          'update_test_postupdate_post_update_first',
          'update_test_postupdate_post_update_second',
          'update_test_postupdate_post_update_test1',
          'update_test_postupdate_post_update_test0',
          'update_test_postupdate_post_update_foo',
          'update_test_postupdate_post_update_bar',
          'update_test_postupdate_post_update_baz',
        ];
        $this->assertSame($expected, $existing_updates);
        break;
    }
  }

  /**
   * Asserts the module post update functions after uninstall.
   *
   * @param string $module
   *   The module that got installed.
   */
  protected function assertUninstallModuleUpdates($module) {
    /** @var \Drupal\Core\Update\UpdateRegistry $post_update_registry */
    $post_update_registry = \Drupal::service('update.post_update_registry');
    $all_update_functions = $post_update_registry->getPendingUpdateFunctions();

    switch ($module) {
      case 'update_test_postupdate':
        $this->assertEmpty(array_intersect(['update_test_postupdate_post_update_first'], $all_update_functions), 'Asserts that no pending post update functions are available.');

        $existing_updates = \Drupal::keyValue('post_update')->get('existing_updates', []);
        $this->assertEmpty(array_intersect(['update_test_postupdate_post_update_first'], $existing_updates), 'Asserts that no post update functions are stored in keyvalue store.');
        break;
    }
  }

  /**
   * Verifies a module's help.
   *
   * Verifies that the module help page from hook_help() exists and can be
   * displayed, and that it contains the phrase "Foo Bar module", where "Foo
   * Bar" is the name of the module from the .info.yml file.
   *
   * @param string $module
   *   Machine name of the module to verify.
   * @param string $name
   *   Human-readable name of the module to verify.
   */
  protected function assertHelp($module, $name) {
    $this->drupalGet('admin/help/' . $module);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText($name . ' module', "'$name module' is on the help page for $module");
    $this->assertLink('online documentation for the ' . $name . ' module', 0, "Correct online documentation link is in the help page for $module");
  }

  /**
   * Deletes forum taxonomy terms, so Forum can be uninstalled.
   */
  protected function preUninstallForum() {
    // There only should be a 'General discussion' term in the 'forums'
    // vocabulary, but just delete any terms there in case the name changes.
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'forums');
    $ids = $query->execute();
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $storage->loadMultiple($ids);
    $storage->delete($terms);
  }

}
