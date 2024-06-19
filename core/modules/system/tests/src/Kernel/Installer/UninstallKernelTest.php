<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Installer;

use Drupal\KernelTests\KernelTestBase;
use Drupal\module_test\PluginManagerCacheClearer;

/**
 * Tests the uninstallation of modules.
 *
 * @group Module
 */
class UninstallKernelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installConfig(['media']);
  }

  /**
   * Tests uninstalling media and file modules.
   */
  public function testUninstallMedia(): void {
    // Media creates a file field that is removed on uninstall, ensure that it
    // is fully deleted (as it is empty) and that file then can be uninstalled
    // as well.
    \Drupal::service('module_installer')->uninstall(['media']);
    \Drupal::service('module_installer')->uninstall(['file']);
  }

  /**
   * Tests uninstalling a module with a plugin cache clearer service.
   */
  public function testUninstallPluginCacheClear(): void {
    \Drupal::service('module_installer')->install(['module_test']);
    $this->assertFalse($this->container->get('state')->get(PluginManagerCacheClearer::class));
    \Drupal::service('module_installer')->install(['dblog']);
    $this->assertTrue($this->container->get('state')->get(PluginManagerCacheClearer::class));

    // The plugin cache clearer service should be called during dblog uninstall
    // without the dependency.
    \Drupal::service('module_installer')->uninstall(['dblog']);
    $this->assertFalse($this->container->get('state')->get(PluginManagerCacheClearer::class));

    // The service should not be called during module_test uninstall.
    $this->container->get('state')->delete(PluginManagerCacheClearer::class);
    \Drupal::service('module_installer')->uninstall(['module_test']);
    $this->assertNull($this->container->get('state')->get(PluginManagerCacheClearer::class));
  }

}
