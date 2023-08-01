<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configuration entity status functionality.
 *
 * @group config
 */
class ConfigEntityStatusTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['config_test'];

  /**
   * Tests the enabling/disabling of entities.
   */
  public function testCRUD() {
    $entity = \Drupal::entityTypeManager()->getStorage('config_test')->create([
      'id' => $this->randomMachineName(),
    ]);
    $this->assertTrue($entity->status(), 'Default status is enabled.');
    $entity->save();
    $this->assertTrue($entity->status(), 'Status is enabled after saving.');

    $entity->disable()->save();
    $this->assertFalse($entity->status(), 'Entity is disabled after disabling.');

    $entity->enable()->save();
    $this->assertTrue($entity->status(), 'Entity is enabled after enabling.');

    $entity = \Drupal::entityTypeManager()->getStorage('config_test')->load($entity->id());
    $this->assertTrue($entity->status(), 'Status is enabled after reload.');
  }

}
