<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group system
 */
class PermissionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * Tests the 'access content' permission is provided by the System module.
   */
  public function testAccessContentPermission() {
    // Uninstalling modules requires the users_data table to exist.
    $this->installSchema('user', ['users_data']);

    $permissions = $this->container->get('user.permissions')->getPermissions();
    $this->assertSame('system', $permissions['access content']['provider']);

    // Install the 'node' module, assert that it is now the 'node' module
    // providing the 'access content' permission.
    $this->container->get('module_installer')->install(['node']);

    $permissions = $this->container->get('user.permissions')->getPermissions();
    $this->assertSame('system', $permissions['access content']['provider']);

    // Uninstall the 'node' module, assert that it is again the 'system' module.
    $this->container->get('module_installer')->uninstall(['node']);

    $permissions = $this->container->get('user.permissions')->getPermissions();
    $this->assertSame('system', $permissions['access content']['provider']);
  }

}
