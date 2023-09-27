<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Runs a series of generic tests for one module.
 */
abstract class GenericModuleTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'help',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    \Drupal::service('module_installer')->install([$module]);
    $info = \Drupal::service('extension.list.module')->getExtensionInfo($module);
    if (!empty($info['required']) && !empty($info['hidden'])) {
      $this->markTestSkipped('Nothing to assert for hidden, required modules.');
    }
    $this->drupalLogin($this->rootUser);
    $this->assertHookHelp($module);

    if (empty($info['required'])) {
      $connection = Database::getConnection();

      // When the database driver is provided by a module, then that module
      // cannot be uninstalled.
      if ($module !== $connection->getProvider()) {
        // Check that the module can be uninstalled and then re-installed again.
        $this->preUnInstallSteps();
        $this->assertTrue(\Drupal::service('module_installer')->uninstall([$module]), "Failed to uninstall '$module' module");
        $this->assertTrue(\Drupal::service('module_installer')->install([$module]), "Failed to install '$module' module");
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
