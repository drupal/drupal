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
  public static $modules = array('config_test');

  /**
   * Tests the enabling/disabling of entities.
   */
  function testCRUD() {
    $entity = entity_create('config_test', array(
      'id' => strtolower($this->randomMachineName()),
    ));
    $this->assertTrue($entity->status(), 'Default status is enabled.');
    $entity->save();
    $this->assertTrue($entity->status(), 'Status is enabled after saving.');

    $entity->disable()->save();
    $this->assertFalse($entity->status(), 'Entity is disabled after disabling.');

    $entity->enable()->save();
    $this->assertTrue($entity->status(), 'Entity is enabled after enabling.');

    $entity = entity_load('config_test', $entity->id());
    $this->assertTrue($entity->status(), 'Status is enabled after reload.');
  }

}
