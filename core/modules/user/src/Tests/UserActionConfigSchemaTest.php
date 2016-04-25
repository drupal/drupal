<?php

namespace Drupal\user\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Ensures the user action for adding and removing roles have valid config
 * schema.
 *
 * @group user
 */
class UserActionConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user');

  /**
   * Tests whether the user action config schema are valid.
   */
  function testValidUserActionConfigSchema() {
    $rid = strtolower($this->randomMachineName(8));
    Role::create(array('id' => $rid))->save();

    // Test user_add_role_action configuration.
    $config = $this->config('system.action.user_add_role_action.' . $rid);
    $this->assertEqual($config->get('id'), 'user_add_role_action.' . $rid);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());

    // Test user_remove_role_action configuration.
    $config = $this->config('system.action.user_remove_role_action.' . $rid);
    $this->assertEqual($config->get('id'), 'user_remove_role_action.' . $rid);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());
  }

}
