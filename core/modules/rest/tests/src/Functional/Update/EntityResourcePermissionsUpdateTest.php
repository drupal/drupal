<?php

namespace Drupal\Tests\rest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that existing sites continue to use permissions for EntityResource.
 *
 * @see https://www.drupal.org/node/2664780
 *
 * @group rest
 * @group legacy
 */
class EntityResourcePermissionsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest', 'serialization'];

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.rest-rest_update_8203.php',
    ];
  }

  /**
   * Tests rest_update_8203().
   */
  public function testBcEntityResourcePermissionSettingAdded() {
    $permission_handler = $this->container->get('user.permissions');

    $is_rest_resource_permission = function ($permission) {
      return $permission['provider'] === 'rest' && (string) $permission['title'] !== 'Administer REST resource configuration';
    };

    // Make sure we have the expected values before the update.
    $rest_settings = $this->config('rest.settings');
    $this->assertFalse(array_key_exists('bc_entity_resource_permissions', $rest_settings->getRawData()));
    $this->assertEqual([], array_filter($permission_handler->getPermissions(), $is_rest_resource_permission));

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $rest_settings = $this->config('rest.settings');
    $this->assertTrue(array_key_exists('bc_entity_resource_permissions', $rest_settings->getRawData()));
    $this->assertTrue($rest_settings->get('bc_entity_resource_permissions'));
    $rest_permissions = array_keys(array_filter($permission_handler->getPermissions(), $is_rest_resource_permission));
    $this->assertEqual(['restful delete entity:node', 'restful get entity:node', 'restful patch entity:node', 'restful post entity:node'], $rest_permissions);
  }

}
