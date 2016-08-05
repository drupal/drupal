<?php

namespace Drupal\rest\Tests;

use Drupal\Core\Url;

/**
 * Tests the deletion of resources.
 *
 * @group rest
 */
class DeleteTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test', 'node');

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
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // Create an entity programmatically.
      $entity = $this->entityCreate($entity_type);
      $entity->save();
      // Try first to delete over REST API without the CSRF token.
      $this->httpRequest($entity->urlInfo(), 'DELETE', NULL, NULL, TRUE);
      $this->assertResponse(403, 'X-CSRF-Token request header is missing');
      // Delete it over the REST API.
      $response = $this->httpRequest($entity->urlInfo(), 'DELETE');
      // Clear the static cache with entity_load(), otherwise we won't see the
      // update.
      $storage = $this->container->get('entity_type.manager')
        ->getStorage($entity_type);
      $storage->resetCache([$entity->id()]);
      $entity = $storage->load($entity->id());
      $this->assertFalse($entity, $entity_type . ' entity is not in the DB anymore.');
      $this->assertResponse('204', 'HTTP response code is correct.');
      $this->assertEqual($response, '', 'Response body is empty.');

      // Try to delete an entity that does not exist.
      $response = $this->httpRequest(Url::fromRoute('entity.' . $entity_type . '.canonical', [$entity_type => 9999]), 'DELETE');
      $this->assertResponse(404);
      $this->assertText('The requested page could not be found.');

      // Try to delete an entity without proper permissions.
      $this->drupalLogout();
      // Re-save entity to the database.
      $entity = $this->entityCreate($entity_type);
      $entity->save();
      $this->httpRequest($entity->urlInfo(), 'DELETE');
      $this->assertResponse(403);
      $storage->resetCache([$entity->id()]);
      $this->assertNotIdentical(FALSE, $storage->load($entity->id()),
        'The ' . $entity_type . ' entity is still in the database.');
    }
    // Try to delete a resource which is not REST API enabled.
    $this->enableService(FALSE);
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->httpRequest($account->urlInfo(), 'DELETE');
    $user_storage = $this->container->get('entity.manager')->getStorage('user');
    $user_storage->resetCache(array($account->id()));
    $user = $user_storage->load($account->id());
    $this->assertEqual($account->id(), $user->id(), 'User still exists in the database.');
    $this->assertResponse(405);
  }

}
