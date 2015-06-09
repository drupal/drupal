<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Module\DependencyTest.
 */

namespace Drupal\system\Tests\Module;
use Drupal\Component\Utility\Unicode;

/**
 * Enable module without dependency enabled.
 *
 * @group Module
 */
class DependencyTest extends ModuleTestBase {
  /**
   * Checks functionality of project namespaces for dependencies.
   */
  function testProjectNamespaceForDependencies() {
    $edit = array(
      'modules[Core][filter][enable]' => TRUE,
    );
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    // Enable module with project namespace to ensure nothing breaks.
    $edit = array(
      'modules[Testing][system_project_namespace_test][enable]' => TRUE,
    );
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->assertModules(array('system_project_namespace_test'), TRUE);
  }

  /**
   * Attempts to enable the Content Translation module without Language enabled.
   */
  function testEnableWithoutDependency() {
    // Attempt to enable Content Translation without Language enabled.
    $edit = array();
    $edit['modules[Multilingual][content_translation][enable]'] = 'content_translation';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('Some required modules must be enabled'), 'Dependency required.');

    $this->assertModules(array('content_translation', 'language'), FALSE);

    // Assert that the language tables weren't enabled.
    $this->assertTableCount('language', FALSE);

    $this->drupalPostForm(NULL, NULL, t('Continue'));
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
    $this->assertRaw(t('@module (<span class="admin-missing">missing</span>)', array('@module' => Unicode::ucfirst('_missing_dependency'))), 'A module with missing dependencies is marked as such.');
    $checkbox = $this->xpath('//input[@type="checkbox" and @disabled="disabled" and @name="modules[Testing][system_dependencies_test][enable]"]');
    $this->assert(count($checkbox) == 1, 'Checkbox for the module is disabled.');
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
    \Drupal::service('module_installer')->install(array('comment'));

    $this->assertModules(array('requirements1_test'), FALSE);
    $this->assertModules(array('requirements2_test'), FALSE);

    // Attempt to install both modules at the same time.
    $edit = array();
    $edit['modules[Testing][requirements1_test][enable]'] = 'requirements1_test';
    $edit['modules[Testing][requirements2_test][enable]'] = 'requirements2_test';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));

    // Makes sure the modules were NOT installed.
    $this->assertText(t('Requirements 1 Test failed requirements'), 'Modules status has been updated.');
    $this->assertModules(array('requirements1_test'), FALSE);
    $this->assertModules(array('requirements2_test'), FALSE);

    // Makes sure that already enabled modules the failing modules depend on
    // were not disabled.
    $this->assertModules(array('comment'), TRUE);
  }

  /**
   * Tests that module dependencies are enabled in the correct order in the UI.
   *
   * Dependencies should be enabled before their dependents.
   */
  function testModuleEnableOrder() {
    \Drupal::service('module_installer')->install(array('module_test'), FALSE);
    $this->resetAll();
    $this->assertModules(array('module_test'), TRUE);
    \Drupal::state()->set('module_test.dependency', 'dependency');
    // module_test creates a dependency chain:
    // - color depends on config
    // - config depends on help
    $expected_order = array('help', 'config', 'color');

    // Enable the modules through the UI, verifying that the dependency chain
    // is correct.
    $edit = array();
    $edit['modules[Core][color][enable]'] = 'color';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->assertModules(array('color'), FALSE);
    // Note that dependencies are sorted alphabetically in the confirmation
    // message.
    $this->assertText(t('You must enable the Configuration Manager, Help modules to install Color.'));

    $edit['modules[Core][config][enable]'] = 'config';
    $edit['modules[Core][help][enable]'] = 'help';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->assertModules(array('color', 'config', 'help'), TRUE);

    // Check the actual order which is saved by module_test_modules_enabled().
    $module_order = \Drupal::state()->get('module_test.install_order') ?: array();
    $this->assertIdentical($module_order, $expected_order);
  }

  /**
   * Tests attempting to uninstall a module that has installed dependents.
   */
  function testUninstallDependents() {
    // Enable the forum module.
    $edit = array('modules[Core][forum][enable]' => 'forum');
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->drupalPostForm(NULL, array(), t('Continue'));
    $this->assertModules(array('forum'), TRUE);

    // Check that the comment module cannot be uninstalled.
    $this->drupalGet('admin/modules/uninstall');
    $checkbox = $this->xpath('//input[@type="checkbox" and @name="uninstall[comment]" and @disabled="disabled"]');
    $this->assert(count($checkbox) == 1, 'Checkbox for uninstalling the comment module is disabled.');

    // Delete any forum terms.
    $vid = $this->config('forum.settings')->get('vocabulary');
    // Ensure taxonomy has been loaded into the test-runner after forum was
    // enabled.
    \Drupal::moduleHandler()->load('taxonomy');
    $terms = entity_load_multiple_by_properties('taxonomy_term', ['vid' => $vid]);
    foreach ($terms as $term) {
      $term->delete();
    }
    // Uninstall the forum module, and check that taxonomy now can also be
    // uninstalled.
    $edit = array('uninstall[forum]' => 'forum');
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

    // Uninstall comment module.
    $edit = array('uninstall[comment]' => 'comment');
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
  }

}
