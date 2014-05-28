<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserActionConfigSchemaTest.
 */

namespace Drupal\user\Tests;

use Drupal\config\Tests\ConfigSchemaTestBase;

/**
 * Tests the User action config schema.
 */
class UserActionConfigSchemaTest extends ConfigSchemaTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user');

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'User action config schema',
      'description' => 'Ensures the user action for adding and removing roles have valid config schema.',
      'group' => 'User',
    );
  }

  /**
   * Tests whether the user action config schema are valid.
   */
  function testValidUserActionConfigSchema() {
    $rid = $this->drupalCreateRole(array());

    // Test user_add_role_action configuration.
    $config = \Drupal::config('system.action.user_add_role_action.' . $rid);
    $this->assertEqual($config->get('id'), 'user_add_role_action.' . $rid);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());

    // Test user_remove_role_action configuration.
    $config = \Drupal::config('system.action.user_remove_role_action.' . $rid);
    $this->assertEqual($config->get('id'), 'user_remove_role_action.' . $rid);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());
  }

}
