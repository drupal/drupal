<?php

namespace Drupal\Tests\update\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Update module storage is cleared correctly.
 *
 * @group update
 */
class UpdateStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'update',
  ];

  /**
   * Tests the Update module storage is cleared correctly.
   */
  public function testUpdateStorage() {
    // Setting values in both key stores, then installing the module and
    // testing if these key values are cleared.
    $keyvalue_update = $this->container->get('keyvalue.expirable')->get('update');
    $keyvalue_update->set('key', 'some value');
    $keyvalue_update_available_release = $this->container->get('keyvalue.expirable')->get('update_available_release');
    $keyvalue_update_available_release->set('key', 'some value');
    $this->container->get('module_installer')->install(['help']);
    $this->assertNull($keyvalue_update->get('key'));
    $this->assertNull($keyvalue_update_available_release->get('key'));

    // Setting new values in both key stores, then uninstalling the module and
    // testing if these new key values are cleared.
    $keyvalue_update->set('another_key', 'some value');
    $keyvalue_update_available_release->set('another_key', 'some value');
    $this->container->get('module_installer')->uninstall(['help']);
    $this->assertNull($keyvalue_update->get('another_key'));
    $this->assertNull($keyvalue_update_available_release->get('another_key'));
  }

}
