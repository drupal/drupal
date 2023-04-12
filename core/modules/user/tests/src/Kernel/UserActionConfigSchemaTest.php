<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests that the config schema is valid when roles are added or removed.
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
  protected static $modules = ['system', 'user'];

  /**
   * Tests whether the user action config schema are valid.
   */
  public function testValidUserActionConfigSchema() {
    $rid = strtolower($this->randomMachineName(8));
    Role::create(['id' => $rid, 'label' => $rid])->save();

    // Test user_add_role_action configuration.
    $config = $this->config('system.action.user_add_role_action.' . $rid);
    $this->assertEquals('user_add_role_action.' . $rid, $config->get('id'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());

    // Test user_remove_role_action configuration.
    $config = $this->config('system.action.user_remove_role_action.' . $rid);
    $this->assertEquals('user_remove_role_action.' . $rid, $config->get('id'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());
  }

}
