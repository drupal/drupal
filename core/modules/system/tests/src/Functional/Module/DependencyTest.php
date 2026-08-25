<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Component\Serialization\Yaml;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Enable module without dependency enabled.
 */
#[Group('Module')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class DependencyTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    // Create a module for testing semver support.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/../modules/system_core_semver_test', NULL, TRUE);
    $contents = <<<INFO
name: 'System core ^8 version test'
type: module
description: 'Support module for testing core using semver.'
package: Testing
version: 1.0.0

INFO;
    // Add the core_version_requirement key.
    $version = explode('.', \Drupal::VERSION, 2);
    $contents .= "core_version_requirement: ^$version[0]\n";
    file_put_contents($this->publicFilesDirectory . '/../modules/system_core_semver_test/system_core_semver_test.info.yml', $contents);
  }

  /**
   * Checks functionality of project namespaces for dependencies.
   */
  public function testProjectNamespaceForDependencies(): void {
    $edit = [
      'modules[filter][enable]' => TRUE,
    ];
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    // Enable module with project namespace to ensure nothing breaks.
    $edit = [
      'modules[system_project_namespace_test][enable]' => TRUE,
    ];
    $this->submitForm($edit, 'Install');
    $this->assertModules(['system_project_namespace_test'], TRUE);
  }

  /**
   * Tests visiting admin/modules when a module outside of core has no version.
   */
  public function testNoVersionInfo(): void {
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

    // Remove the version requirement from the dependency definition.
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
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('2 modules have been installed: System no module version dependency test, System no module version test.');

    // Ensure status report is working.
    $this->drupalLogin($this->createUser(['administer site configuration']));
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests enabling modules with different core version specifications.
   */
  public function testCoreCompatibility(): void {
    $assert_session = $this->assertSession();

    // Test incompatible 'core_version_requirement'.
    $this->drupalGet('admin/modules');
    $assert_session->fieldDisabled('modules[system_core_incompatible_semver_test][enable]');

    // Test compatible 'core_version_requirement' and compatible 'core'.
    $assert_session->fieldEnabled('modules[common_test][enable]');
    $assert_session->fieldEnabled('modules[system_core_semver_test][enable]');

    // Ensure the modules can actually be installed.
    $edit['modules[common_test][enable]'] = 'common_test';
    $edit['modules[system_core_semver_test][enable]'] = 'system_core_semver_test';
    $this->submitForm($edit, 'Install');
    $this->assertModules(['common_test', 'system_core_semver_test'], TRUE);
  }

  /**
   * Tests that module dependencies install text is formatted correctly.
   */
  public function testModuleDependencyMessages(): void {
    // Check the module install text with 1 module dependency.
    $edit = [];
    $edit['modules[file][enable]'] = 'file';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('You must install the following module to install File:Field');

    // Check the module install text with 2 module dependencies.
    \Drupal::service('module_installer')->install(['module_test'], FALSE);
    \Drupal::state()->set('module_test.dependency', 'dependency');
    $this->drupalGet('admin/modules');
    $edit = [];
    $edit['modules[dblog][enable]'] = 'dblog';
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('You must install the following modules to install Database Logging:Configuration ManagerHelp');

    // Check the module install text with more than 2 module dependencies.
    $edit = [];
    $edit['modules[navigation][enable]'] = 'navigation';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('You must install the following modules to install Navigation:BlockFileFieldLayout BuilderLayout DiscoveryContextual Links');
  }

}
