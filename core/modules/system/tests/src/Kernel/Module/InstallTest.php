<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Module;

use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Extension\ExtensionNameReservedException;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the installation of modules.
 *
 * @group Module
 */
class InstallTest extends KernelTestBase {

  /**
   * The module installer service.
   */
  protected ModuleInstallerInterface $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleInstaller = $this->container->get('module_installer');
    $this->moduleInstaller->install([
      'module_test',
      'system',
      'user',
    ]);
  }

  /**
   * Verify that drupal_get_schema() can be used during module installation.
   */
  public function testGetSchemaAtInstallTime(): void {
    // @see module_test_install()
    $database = $this->container->get('database');
    $value = $database->select('module_test')
      ->fields('module_test', ['data'])
      ->execute()
      ->fetchField();
    $this->assertEquals('varchar', $value);
  }

  /**
   * Tests enabling User module once more.
   *
   * Regression: The installer might enable a module twice due to automatic
   * dependency resolution. A bug caused the stored weight for user.module to
   * be an array.
   */
  public function testEnableUserTwice(): void {
    $this->moduleInstaller->install(['user'], FALSE);
    $this->assertSame(0, $this->config('core.extension')->get('module.user'));

    // To avoid false positives, ensure that a module that does not exist does
    // not return exactly zero.
    $this->assertNotSame(0, $this->config('core.extension')
      ->get('module.does_not_exist'));
  }

  /**
   * Tests recorded schema versions of early installed modules in the installer.
   */
  public function testRequiredModuleSchemaVersions(): void {
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $version = $update_registry->getInstalledVersion('system');
    $this->assertGreaterThan(0, $version);
    $version = $update_registry->getInstalledVersion('user');
    $this->assertGreaterThan(0, $version);

    $post_update_key_value = \Drupal::keyValue('post_update');
    $existing_updates = $post_update_key_value->get('existing_updates', []);
    $this->assertContains('module_test_post_update_test', $existing_updates);
  }

  /**
   * Ensures that post update functions are removed on uninstallation.
   */
  public function testUninstallPostUpdateFunctions(): void {
    // First, to avoid false positives, ensure that the post_update function
    // exists while the module is still installed.
    $post_update_key_value = $this->container->get('keyvalue')
      ->get('post_update');
    $existing_updates = $post_update_key_value->get('existing_updates', []);
    $this->assertContains('module_test_post_update_test', $existing_updates);

    // Uninstall the module.
    $this->moduleInstaller->uninstall(['module_test']);

    // Ensure the post update function is no longer listed.
    $existing_updates = $post_update_key_value->get('existing_updates', []);
    $this->assertNotContains('module_test_post_update_test', $existing_updates);
  }

  /**
   * Tests that an exception is thrown when a module name is too long.
   */
  public function testModuleNameLength(): void {
    $module_name = 'invalid_module_name_over_the_maximum_allowed_character_length';
    $this->expectException(ExtensionNameLengthException::class);
    $this->expectExceptionMessage("Module name 'invalid_module_name_over_the_maximum_allowed_character_length' is over the maximum allowed length of 50 characters");
    $this->moduleInstaller->install([$module_name]);
  }

  /**
   * Tests that an exception is thrown when a module name is too long.
   *
   * We do this without checking dependencies for the module to install.
   */
  public function testModuleNameLengthWithoutDependencyCheck(): void {
    $module_name = 'invalid_module_name_over_the_maximum_allowed_character_length';
    $this->expectException(ExtensionNameLengthException::class);
    $this->expectExceptionMessage("Module name 'invalid_module_name_over_the_maximum_allowed_character_length' is over the maximum allowed length of 50 characters");
    $this->moduleInstaller->install([$module_name], FALSE);
  }

  /**
   * Tests installing a module with the same name as an enabled theme.
   */
  public function testInstallModuleSameNameAsTheme(): void {
    $name = 'name_collision_test';

    // Install and uninstall the module.
    $this->moduleInstaller->install([$name]);
    $this->moduleInstaller->uninstall([$name]);

    // Install the theme, then the module.
    $this->container->get('theme_installer')->install([$name]);
    $message = "Module name {$name} is already in use by an installed theme.";
    $this->expectException(ExtensionNameReservedException::class);
    $this->expectExceptionMessage($message);
    $this->moduleInstaller->install([$name]);
  }

}
