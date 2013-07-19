<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\DependencyTest.
 */

namespace Drupal\system\Tests\Module;

/**
 * Tests module dependency functionality.
 */
class DependencyTest extends ModuleTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Module dependencies',
      'description' => 'Enable module without dependency enabled.',
      'group' => 'Module',
    );
  }

  /**
   * Attempts to enable the Content Translation module without Language enabled.
   */
  function testEnableWithoutDependency() {
    // Attempt to enable Content Translation without Language enabled.
    $edit = array();
    $edit['modules[Multilingual][content_translation][enable]'] = 'content_translation';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('Some required modules must be enabled'), 'Dependency required.');

    $this->assertModules(array('content_translation', 'language'), FALSE);

    // Assert that the language tables weren't enabled.
    $this->assertTableCount('language', FALSE);

    $this->drupalPost(NULL, NULL, t('Continue'));
    $this->assertText(t('The configuration options have been saved.'), 'Modules status has been updated.');

    $this->assertModules(array('content_translation', 'language'), TRUE);

    // Assert that the language YAML files were created.
    $storage = $this->container->get('config.storage');
    $this->assertTrue(count($storage->listAll('language.entity.')) > 0, 'Language config entity files exist.');
  }

  /**
   * Attempts to enable a module with a missing dependency.
   */
  function testMissingModules() {
    // Test that the system_dependencies_test module is marked
    // as missing a dependency.
    $this->drupalGet('admin/modules');
    $this->assertRaw(t('@module (<span class="admin-missing">missing</span>)', array('@module' => drupal_ucfirst('_missing_dependency'))), 'A module with missing dependencies is marked as such.');
    $checkbox = $this->xpath('//input[@type="checkbox" and @disabled="disabled" and @name="modules[Testing][system_dependencies_test][enable]"]');
    $this->assert(count($checkbox) == 1, 'Checkbox for the module is disabled.');

    // Force enable the system_dependencies_test module.
    module_enable(array('system_dependencies_test'), FALSE);

    // Verify that the module is forced to be disabled when submitting
    // the module page.
    $this->drupalPost('admin/modules', array(), t('Save configuration'));
    $this->assertText(t('The @module module is missing, so the following module will be disabled: @depends.', array('@module' => '_missing_dependency', '@depends' => 'System dependency test')), 'The module missing dependencies will be disabled.');

    // Confirm.
    $this->drupalPost(NULL, NULL, t('Continue'));

    // Verify that the module has been disabled.
    $this->assertModules(array('system_dependencies_test'), FALSE);
  }

  /**
   * Tests enabling a module that depends on an incompatible version of a module.
   */
  function testIncompatibleModuleVersionDependency() {
    // Test that the system_incompatible_module_version_dependencies_test is
    // marked as having an incompatible dependency.
    $this->drupalGet('admin/modules');
    $this->assertRaw(t('@module (<span class="admin-missing">incompatible with</span> version @version)', array(
      '@module' => 'System incompatible module version test (>2.0)',
      '@version' => '1.0',
    )), 'A module that depends on an incompatible version of a module is marked as such.');
    $checkbox = $this->xpath('//input[@type="checkbox" and @disabled="disabled" and @name="modules[Testing][system_incompatible_module_version_dependencies_test][enable]"]');
    $this->assert(count($checkbox) == 1, 'Checkbox for the module is disabled.');
  }

  /**
   * Tests enabling a module that depends on a module with an incompatible core version.
   */
  function testIncompatibleCoreVersionDependency() {
    // Test that the system_incompatible_core_version_dependencies_test is
    // marked as having an incompatible dependency.
    $this->drupalGet('admin/modules');
    $this->assertRaw(t('@module (<span class="admin-missing">incompatible with</span> this version of Drupal core)', array(
      '@module' => 'System incompatible core version test',
    )), 'A module that depends on a module with an incompatible core version is marked as such.');
    $checkbox = $this->xpath('//input[@type="checkbox" and @disabled="disabled" and @name="modules[Testing][system_incompatible_core_version_dependencies_test][enable]"]');
    $this->assert(count($checkbox) == 1, 'Checkbox for the module is disabled.');
  }

  /**
   * Tests enabling a module that depends on a module which fails hook_requirements().
   */
  function testEnableRequirementsFailureDependency() {
    module_enable(array('comment'));

    $this->assertModules(array('requirements1_test'), FALSE);
    $this->assertModules(array('requirements2_test'), FALSE);

    // Attempt to install both modules at the same time.
    $edit = array();
    $edit['modules[Testing][requirements1_test][enable]'] = 'requirements1_test';
    $edit['modules[Testing][requirements2_test][enable]'] = 'requirements2_test';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));

    // Makes sure the modules were NOT installed.
    $this->assertText(t('Requirements 1 Test failed requirements'), 'Modules status has been updated.');
    $this->assertModules(array('requirements1_test'), FALSE);
    $this->assertModules(array('requirements2_test'), FALSE);

    // Makes sure that already enabled modules the failing modules depend on
    // were not disabled.
    $this->assertModules(array('comment'), TRUE);

  }

  /**
   * Tests that module dependencies are enabled in the correct order via the
   * UI. Dependencies should be enabled before their dependents.
   */
  function testModuleEnableOrder() {
    module_enable(array('module_test'), FALSE);
    $this->resetAll();
    $this->assertModules(array('module_test'), TRUE);
    \Drupal::state()->set('module_test.dependency', 'dependency');
    // module_test creates a dependency chain:
    // - forum depends on taxonomy, comment, datetime, history, and ban (via module_test)
    // - taxonomy depends on options
    // - options depends on number
    // - ban depends on php (via module_test)
    // The correct enable order is:
    $expected_order = array('php', 'ban', 'datetime', 'comment', 'history', 'number', 'options', 'taxonomy', 'forum');

    // Enable the modules through the UI, verifying that the dependency chain
    // is correct.
    $edit = array();
    $edit['modules[Core][forum][enable]'] = 'forum';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertModules(array('forum'), FALSE);
    $this->assertText(t('You must enable the History, Taxonomy, Options, Number, Comment, Datetime, Ban, PHP Filter modules to install Forum.'));
    $edit['modules[Core][history][enable]'] = 'history';
    $edit['modules[Core][options][enable]'] = 'options';
    $edit['modules[Core][number][enable]'] = 'number';
    $edit['modules[Core][taxonomy][enable]'] = 'taxonomy';
    $edit['modules[Core][comment][enable]'] = 'comment';
    $edit['modules[Core][datetime][enable]'] = 'datetime';
    $edit['modules[Core][ban][enable]'] = 'ban';
    $edit['modules[Core][php][enable]'] = 'php';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertModules(array('forum', 'ban', 'php', 'datetime', 'comment', 'history', 'taxonomy', 'options', 'number'), TRUE);

    // Check the actual order which is saved by module_test_modules_enabled().
    $module_order = \Drupal::state()->get('system_test.module_enable_order') ?: array();
    $this->assertIdentical($module_order, $expected_order);
  }

  /**
   * Tests attempting to uninstall a module that has installed dependents.
   */
  function testUninstallDependents() {
    // Enable the forum module.
    $edit = array('modules[Core][forum][enable]' => 'forum');
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->drupalPost(NULL, array(), t('Continue'));
    $this->assertModules(array('forum'), TRUE);

    // Disable forum and comment. Both should now be installed but disabled.
    $edit = array('modules[Core][forum][enable]' => FALSE);
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertModules(array('forum'), FALSE);
    $edit = array('modules[Core][comment][enable]' => FALSE);
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertModules(array('comment'), FALSE);

    // Check that the taxonomy module cannot be uninstalled.
    $this->drupalGet('admin/modules/uninstall');
    $checkbox = $this->xpath('//input[@type="checkbox" and @disabled="disabled" and @name="uninstall[comment]"]');
    $this->assert(count($checkbox) == 1, 'Checkbox for uninstalling the comment module is disabled.');

    // Uninstall the forum module, and check that taxonomy now can also be
    // uninstalled.
    $edit = array('uninstall[forum]' => 'forum');
    $this->drupalPost('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPost(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
    $edit = array('uninstall[comment]' => 'comment');
    $this->drupalPost('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPost(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
  }
}
