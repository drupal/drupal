<?php

namespace Drupal\Tests\system\Kernel\Installer;

use Drupal\KernelTests\KernelTestBase;

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
  public static $modules = ['system', 'user', 'field', 'file', 'image', 'media'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installConfig(['media']);
  }

  /**
   * Tests uninstalling media and file modules.
   */
  public function testUninstallMedia() {
    // Media creates a file field that is removed on uninstall, ensure that it
    // is fully deleted (as it is empty) and that file then can be uninstalled
    // as well.
    \Drupal::service('module_installer')->uninstall(['media']);
    \Drupal::service('module_installer')->uninstall(['file']);
  }

}
