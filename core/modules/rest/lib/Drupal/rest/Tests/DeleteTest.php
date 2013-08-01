<?php

/**
 * @file
 * Definition of Drupal\rest\test\DeleteTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests resource deletion on user, node and test entities.
 */
class DeleteTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Delete resource',
      'description' => 'Tests the deletion of resources.',
      'group' => 'REST',
    );
  }

  /**
   * Tests several valid and invalid delete requests on all entity types.
   */
  public function testDelete() {
    // Define the entity types we want to test.
    // @todo expand this test to at least users once their access
    // controllers are implemented.
    $entity_types = array('entity_test', 'node');
    foreach ($entity_types as $entity_type) {
      $this->enableService('entity:' . $entity_type, 'DELETE');
      // Create a user account that has the required permissions to delete
      // resources via the REST API.
      $permissions = $this->entityPermissions($entity_type, 'delete');
      $permissions[] = 'restful delete entity:' . $entity_type;
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // Create an entity programmatically.
      $entity = $this->entityCreate($entity_type);
      $entity->save();
      // Delete it over the REST API.
      $response = $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'DELETE');
      // Clear the static cache with entity_load(), otherwise we won't see the
      // update.
      $entity = entity_load($entity_type, $entity->id(), TRUE);
      $this->assertFalse($entity, $entity_type . ' entity is not in the DB anymore.');
      $this->assertResponse('204', 'HTTP response code is correct.');
      $this->assertEqual($response, '', 'Response body is empty.');

      // Try to delete an entity that does not exist.
      $response = $this->httpRequest('entity/' . $entity_type . '/9999', 'DELETE');
      $this->assertResponse(404);
      $decoded = drupal_json_decode($response);
      $this->assertEqual($decoded['error'], 'Entity with ID 9999 not found', 'Response message is correct.');

      // Try to delete an entity without proper permissions.
      $this->drupalLogout();
      // Re-save entity to the database.
      $entity = $this->entityCreate($entity_type);
      $entity->save();
      $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'DELETE');
      $this->assertResponse(403);
      $this->assertNotIdentical(FALSE, entity_load($entity_type, $entity->id(), TRUE), 'The ' . $entity_type . ' entity is still in the database.');
    }
    // Try to delete a resource which is not REST API enabled.
    $this->enableService(FALSE);
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->httpRequest('entity/user/' . $account->id(), 'DELETE');
    $user = entity_load('user', $account->id(), TRUE);
    $this->assertEqual($account->id(), $user->id(), 'User still exists in the database.');
    $this->assertResponse(404);
  }
}
