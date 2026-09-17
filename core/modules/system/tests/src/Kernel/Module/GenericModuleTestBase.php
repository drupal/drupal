<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Module;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Runs a series of generic tests for one module.
 *
 * This test's set-up installs the module's dependencies and install config,
 * unlike typical Kernel tests.
 */
abstract class GenericModuleTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'help',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install system module's config, as the module being tested may expect
    // default config to exist.
    $this->installConfig('system');

    $module = $this->getModule();
    // Install the module, going via the module installer so that its
    // dependencies are installed too.
    $status = $this->container->get('module_installer')->install([$module]);
    $this->assertTrue($status);

    $this->installConfig('user');
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('user');

    $this->installConfig($module);
  }

  /**
   * Get the module name.
   *
   * @return string
   *   The module to test.
   */
  protected function getModule(): string {
    return explode('\\', get_class($this))[2];
  }

  /**
   * Checks some generic things about a module.
   */
  public function testModuleGenericIssues(): void {
    $module = $this->getModule();
    $info = \Drupal::service('extension.list.module')->getExtensionInfo($module);
    if (!empty($info['required']) && !empty($info['hidden'])) {
      $this->markTestSkipped('Nothing to assert for hidden, required modules.');
    }

    $anonymous_role = $this->container->get('entity_type.manager')->getStorage('user_role')->loadOverrideFree(AccountInterface::ANONYMOUS_ROLE);
    $anonymous_role->grantPermission('access help pages')
      ->save();
    $this->assertHookHelp($module);

    if (empty($info['required'])) {
      $connection = Database::getConnection();

      // The module that provides the database driver, or is a dependency of
      // the database driver, cannot be uninstalled.
      $database_module_extension = \Drupal::service(ModuleExtensionList::class)->get($connection->getProvider());
      $database_modules_required = $database_module_extension->requires ? array_keys($database_module_extension->requires) : [];
      $database_modules_required[] = $connection->getProvider();
      if (!in_array($module, $database_modules_required)) {
        // Check that the module can be uninstalled and then re-installed again.
        $this->preUnInstallSteps();
        $this->assertTrue(\Drupal::service('module_installer')->uninstall([$module]), "Failed to uninstall '$module' module");
        $this->assertTrue(\Drupal::service('module_installer')->install([$module]), "Failed to install '$module' module");
      }
      elseif (!empty($info['hidden'])) {
        // If a database driver is hidden, there will have been no assertions at
        // all, so mark the test skipped.
        $this->markTestSkipped('Nothing to assert for database driver modules.');
      }
    }
  }

  /**
   * Verifies hook_help() syntax.
   *
   * @param string $module
   *   The module.
   */
  protected function assertHookHelp(string $module): void {
    $info = \Drupal::service('extension.list.module')->getExtensionInfo($module);
    if (empty($info['hidden'])) {
      $this->drupalGet('admin/help/' . $module);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains($info['name'] . ' module');
      $this->assertSession()->linkExists('online documentation for the ' . $info['name'] . ' module', 0, "Correct online documentation link is in the help page for $module");
    }
  }

  /**
   * Helper to perform any steps required prior to uninstalling a module.
   */
  protected function preUnInstallSteps(): void {}

}
