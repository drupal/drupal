<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Component\Utility\Unicode;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Enable module without dependency enabled.
 */
#[Group('Module')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class DependencyFormTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Attempts to enable the Content Translation module without Language enabled.
   */
  protected function doTestEnableWithoutDependency(): void {
    // Attempt to enable Content Translation without Language enabled.
    $edit = [];
    $edit['modules[content_translation][enable]'] = 'content_translation';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('Some required modules must be installed');

    $this->assertModules(['content_translation', 'language'], FALSE);

    // Assert that the language module config was not installed.
    $this->assertNoModuleConfig('language');

    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('2 modules have been installed: Content Translation, Language.');
    $this->assertModules(['content_translation', 'language'], TRUE);

    // Assert that the language YAML files were created.
    $storage = $this->container->get('config.storage');
    $this->assertNotEmpty($storage->listAll('language.entity.'), 'Language config entity files exist.');
  }

  /**
   * Tests functionality that can be tested without submitting the form.
   */
  public function testModulesForm(): void {
    $this->doTestMissingRequirements();
    $this->doTestCoreVersionContains8X();
    $this->doTestEnableWithoutDependency();
    $this->doTestEnableRequirementsFailureDependency();
  }

  /**
   * Tests that modules that don't pass requirement checks cannot be enabled.
   */
  protected function doTestMissingRequirements(): void {
    // Test that the system_dependencies_test module is marked
    // as missing a dependency.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains(Unicode::ucfirst('_missing_dependency') . ' (missing)');
    $this->assertSession()->elementTextEquals('xpath', '//tr[@data-drupal-selector="edit-modules-system-dependencies-test"]//span[@class="admin-missing"]', 'missing');
    $this->assertSession()->checkboxNotChecked('modules[system_dependencies_test][enable]');

    // Test that the system_incompatible_module_version_dependencies_test is
    // marked as having an incompatible dependency.
    $this->assertSession()->pageTextContains('System incompatible module version test (>2.0) (incompatible with version 1.0)');
    $this->assertSession()->elementTextEquals('xpath', '//tr[@data-drupal-selector="edit-modules-system-incompatible-module-version-dependencies-test"]//span[@class="admin-missing"]', 'incompatible with');
    $this->assertSession()->fieldDisabled('modules[system_incompatible_module_version_dependencies_test][enable]');

    // Test that the system_incompatible_core_version_dependencies_test is
    // marked as having an incompatible dependency.
    $this->assertSession()->pageTextContains('System core incompatible semver test (incompatible with this version of Drupal core)');
    $this->assertSession()->elementTextEquals('xpath', '//tr[@data-drupal-selector="edit-modules-system-incompatible-core-version-dependencies-test"]//span[@class="admin-missing"]', 'incompatible with');
    $this->assertSession()->fieldDisabled('modules[system_incompatible_core_version_dependencies_test][enable]');

    // Test PHP version requirements.
    $this->assertSession()->pageTextContains('This module requires PHP version 6502.* and is incompatible with PHP version ' . phpversion() . '.');
    $this->assertSession()->fieldDisabled('modules[system_incompatible_php_version_test][enable]');
  }

  /**
   * Tests the dependency checks when core version contains '8.x' within it.
   */
  protected function doTestCoreVersionContains8X(): void {
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
  protected function doTestEnableRequirementsFailureDependency(): void {
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

}
