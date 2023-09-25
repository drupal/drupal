<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Unicode;

/**
 * Enable module without dependency enabled.
 *
 * @group Module
 * @group #slow
 */
class DependencyTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Checks functionality of project namespaces for dependencies.
   */
  public function testProjectNamespaceForDependencies() {
    $edit = [
      'modules[filter][enable]' => TRUE,
    ];
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    // Enable module with project namespace to ensure nothing breaks.
    $edit = [
      'modules[system_project_namespace_test][enable]' => TRUE,
    ];
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertModules(['system_project_namespace_test'], TRUE);
  }

  /**
   * Attempts to enable the Content Translation module without Language enabled.
   */
  public function testEnableWithoutDependency() {
    // Attempt to enable Content Translation without Language enabled.
    $edit = [];
    $edit['modules[content_translation][enable]'] = 'content_translation';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('Some required modules must be enabled');

    $this->assertModules(['content_translation', 'language'], FALSE);

    // Assert that the language module config was not installed.
    $this->assertNoModuleConfig('language');

    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('2 modules have been enabled: Content Translation, Language.');
    $this->assertModules(['content_translation', 'language'], TRUE);

    // Assert that the language YAML files were created.
    $storage = $this->container->get('config.storage');
    $this->assertNotEmpty($storage->listAll('language.entity.'), 'Language config entity files exist.');
  }

  /**
   * Attempts to enable a module with a missing dependency.
   */
  public function testMissingModules() {
    // Test that the system_dependencies_test module is marked
    // as missing a dependency.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains(Unicode::ucfirst('_missing_dependency') . ' (missing)');
    $this->assertSession()->elementTextEquals('xpath', '//tr[@data-drupal-selector="edit-modules-system-dependencies-test"]//span[@class="admin-missing"]', 'missing');
    $this->assertSession()->checkboxNotChecked('modules[system_dependencies_test][enable]');
  }

  /**
   * Tests enabling a module with an incompatible dependency version.
   */
  public function testIncompatibleModuleVersionDependency() {
    // Test that the system_incompatible_module_version_dependencies_test is
    // marked as having an incompatible dependency.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('System incompatible module version test (>2.0) (incompatible with version 1.0)');
    $this->assertSession()->elementTextEquals('xpath', '//tr[@data-drupal-selector="edit-modules-system-incompatible-module-version-dependencies-test"]//span[@class="admin-missing"]', 'incompatible with');
    $this->assertSession()->fieldDisabled('modules[system_incompatible_module_version_dependencies_test][enable]');
  }

  /**
   * Tests enabling a module that depends on a module with an incompatible core version.
   */
  public function testIncompatibleCoreVersionDependency() {
    // Test that the system_incompatible_core_version_dependencies_test is
    // marked as having an incompatible dependency.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('System core incompatible semver test (incompatible with this version of Drupal core)');
    $this->assertSession()->elementTextEquals('xpath', '//tr[@data-drupal-selector="edit-modules-system-incompatible-core-version-dependencies-test"]//span[@class="admin-missing"]', 'incompatible with');
    $this->assertSession()->fieldDisabled('modules[system_incompatible_core_version_dependencies_test][enable]');
  }

  /**
   * Tests visiting admin/modules when a module outside of core has no version.
   */
  public function testNoVersionInfo() {
    // Create a module for testing. We set core_version_requirement to '*' for
    // the test so that it does not need to be updated between major versions.
    $info = [
      'type' => 'module',
      'core_version_requirement' => '*',
      'name' => 'System no module version dependency test',
    ];
    $path = $this->siteDirectory . '/modules/system_no_module_version_dependency_test';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/system_no_module_version_dependency_test.info.yml", Yaml::encode($info));

    // Include a version in the dependency definition, to test the 'incompatible
    // with version' message when no version is given in the required module.
    $info = [
      'type' => 'module',
      'core_version_requirement' => '*',
      'name' => 'System no module version test',
      'dependencies' => ['system_no_module_version_dependency_test(>1.x)'],
    ];
    $path = $this->siteDirectory . '/modules/system_no_module_version_test';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/system_no_module_version_test.info.yml", Yaml::encode($info));

    // Ensure that the module list page is displayed without errors.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('System no module version test');
    $this->assertSession()->pageTextContains('System no module version dependency test (>1.x) (incompatible with version');
    $this->assertSession()->fieldEnabled('modules[system_no_module_version_dependency_test][enable]');
    $this->assertSession()->fieldDisabled('modules[system_no_module_version_test][enable]');

    // Remove the version requirement from the the dependency definition
    $info = [
      'type' => 'module',
      'core_version_requirement' => '*',
      'name' => 'System no module version test',
      'dependencies' => ['system_no_module_version_dependency_test'],
    ];

    $path = $this->siteDirectory . '/modules/system_no_module_version_test';
    file_put_contents("$path/system_no_module_version_test.info.yml", Yaml::encode($info));

    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('System no module version dependency test');
    $this->assertSession()->pageTextContains('System no module version test');

    // Ensure the modules can actually be installed.
    $edit['modules[system_no_module_version_test][enable]'] = 'system_no_module_version_test';
    $edit['modules[system_no_module_version_dependency_test][enable]'] = 'system_no_module_version_dependency_test';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('2 modules have been enabled: System no module version dependency test, System no module version test.');

    // Ensure status report is working.
    $this->drupalLogin($this->createUser(['administer site configuration']));
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests failing PHP version requirements.
   */
  public function testIncompatiblePhpVersionDependency() {
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('This module requires PHP version 6502.* and is incompatible with PHP version ' . phpversion() . '.');
    $this->assertSession()->fieldDisabled('modules[system_incompatible_php_version_test][enable]');
  }

  /**
   * Tests enabling modules with different core version specifications.
   */
  public function testCoreCompatibility() {
    $assert_session = $this->assertSession();

    // Test incompatible 'core_version_requirement'.
    $this->drupalGet('admin/modules');
    $assert_session->fieldDisabled('modules[system_core_incompatible_semver_test][enable]');

    // Test compatible 'core_version_requirement' and compatible 'core'.
    $this->drupalGet('admin/modules');
    $assert_session->fieldEnabled('modules[common_test][enable]');
    $assert_session->fieldEnabled('modules[system_core_semver_test][enable]');

    // Ensure the modules can actually be installed.
    $edit['modules[common_test][enable]'] = 'common_test';
    $edit['modules[system_core_semver_test][enable]'] = 'system_core_semver_test';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertModules(['common_test', 'system_core_semver_test'], TRUE);
  }

  /**
   * Tests the dependency checks when core version contains '8.x' within it.
   */
  public function testCoreVersionContains8X() {
    // Enable the helper module that alters the version and dependencies.
    \Drupal::service('module_installer')->install(['dependency_version_test']);

    // Check that the above module installed OK.
    $this->drupalGet('admin/modules');
    $this->assertModules(['dependency_version_test'], TRUE);

    // Check that test_module dependencies are met and the box is not greyed.
    $this->assertSession()->fieldEnabled('modules[test_module][enable]');
  }

  /**
   * Tests enabling a module that depends on a module which fails hook_requirements().
   */
  public function testEnableRequirementsFailureDependency() {
    \Drupal::service('module_installer')->install(['comment']);

    $this->assertModules(['requirements1_test'], FALSE);
    $this->assertModules(['requirements2_test'], FALSE);

    // Attempt to install both modules at the same time.
    $edit = [];
    $edit['modules[requirements1_test][enable]'] = 'requirements1_test';
    $edit['modules[requirements2_test][enable]'] = 'requirements2_test';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    // Makes sure the modules were NOT installed.
    $this->assertSession()->pageTextContains('Requirements 1 Test failed requirements');
    $this->assertModules(['requirements1_test'], FALSE);
    $this->assertModules(['requirements2_test'], FALSE);

    // Makes sure that already enabled modules the failing modules depend on
    // were not disabled.
    $this->assertModules(['comment'], TRUE);
  }

  /**
   * Tests that module dependencies are enabled in the correct order in the UI.
   *
   * Dependencies should be enabled before their dependents.
   */
  public function testModuleEnableOrder() {
    \Drupal::service('module_installer')->install(['module_test'], FALSE);
    $this->resetAll();
    $this->assertModules(['module_test'], TRUE);
    \Drupal::state()->set('module_test.dependency', 'dependency');
    // module_test creates a dependency chain:
    // - dblog depends on config
    // - config depends on help
    $expected_order = ['help', 'config', 'dblog'];

    // Enable the modules through the UI, verifying that the dependency chain
    // is correct.
    $edit = [];
    $edit['modules[dblog][enable]'] = 'dblog';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertModules(['dblog'], FALSE);
    // Note that dependencies are sorted alphabetically in the confirmation
    // message.
    $this->assertSession()->pageTextContains('You must enable the Configuration Manager, Help modules to install Database Logging.');

    $edit['modules[config][enable]'] = 'config';
    $edit['modules[help][enable]'] = 'help';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertModules(['dblog', 'config', 'help'], TRUE);

    // Check the actual order which is saved by module_test_modules_enabled().
    $module_order = \Drupal::state()->get('module_test.install_order', []);
    $this->assertSame($expected_order, $module_order);
  }

}
