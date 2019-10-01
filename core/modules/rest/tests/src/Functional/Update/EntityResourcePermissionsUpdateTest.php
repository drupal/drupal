<?php

namespace Drupal\Tests\rest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\rest\RestPermissions;

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
    // Make sure we have the expected values before the update.
    $rest_settings = $this->config('rest.settings');
    $this->assertFalse(array_key_exists('bc_entity_resource_permissions', $rest_settings->getRawData()));

    // We can not use the 'user.permissions' service here because some
    // permissions include generated URLs inside their description, thus
    // requiring the path alias system, which is not guaranteed to be working
    // before running the database updates.
    $rest_permissions_callback = \Drupal::service('controller_resolver')->getControllerFromDefinition(RestPermissions::class . '::permissions');
    $rest_permissions = array_keys(call_user_func($rest_permissions_callback));
    $this->assertEquals([], $rest_permissions);

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $rest_settings = $this->config('rest.settings');
    $this->assertTrue(array_key_exists('bc_entity_resource_permissions', $rest_settings->getRawData()));
    $this->assertTrue($rest_settings->get('bc_entity_resource_permissions'));

    $rest_permissions_callback = \Drupal::service('controller_resolver')->getControllerFromDefinition(RestPermissions::class . '::permissions');
    $rest_permissions = array_keys(call_user_func($rest_permissions_callback));
    $this->assertEquals(['restful get entity:node', 'restful post entity:node', 'restful delete entity:node', 'restful patch entity:node'], $rest_permissions);
  }

}
