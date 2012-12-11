<?php

/**
 * @file
 * Definition of Drupal\rest\test\CreateTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests resource creation on user, node and test entities.
 */
class CreateTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rest', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Create resource',
      'description' => 'Tests the creation of resources.',
      'group' => 'REST',
    );
  }

  /**
   * Tests several valid and invalid create requests on all entity types.
   */
  public function testCreate() {
    $serializer = drupal_container()->get('serializer');
    // @todo once EntityNG is implemented for other entity types test all other
    // entity types here as well.
    $entity_type = 'entity_test';

    $this->enableService('entity:' . $entity_type);
    // Create a user account that has the required permissions to create
    // resources via the web API.
    $account = $this->drupalCreateUser(array('restful post entity:' . $entity_type));
    $this->drupalLogin($account);

    $entity_values = $this->entityValues($entity_type);
    $entity = entity_create($entity_type, $entity_values);
    $serialized = $serializer->serialize($entity, 'drupal_jsonld');
    // Create the entity over the web API.
    $response = $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse('201', 'HTTP response code is correct.');

    // Get the new entity ID from the location header and try to read it from
    // the database.
    $location_url = $this->responseHeaders['location'];
    $url_parts = explode('/', $location_url);
    $id = end($url_parts);
    $loaded_entity = entity_load($entity_type, $id);
    $this->assertNotIdentical(FALSE, $loaded_entity, 'The new ' . $entity_type . ' was found in the database.');
    $this->assertEqual($entity->uuid(), $loaded_entity->uuid(), 'UUID of created entity is correct.');
    // @todo Remove the user reference field for now until deserialization for
    // entity references is implemented.
    unset($entity_values['user_id']);
    foreach ($entity_values as $property => $value) {
      $actual_value = $loaded_entity->get($property);
      $this->assertEqual($value, $actual_value->value, 'Created property ' . $property . ' expected: ' . $value . ', actual: ' . $actual_value->value);
    }

    // Try to create an entity without proper permissions.
    $this->drupalLogout();
    $response = $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(403);

    // Try to create a resource which is not web API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest('entity/entity_test', 'POST', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);

    // @todo Once EntityNG is implemented for other entity types add a security
    // test. It should not be possible for example to create a test entity on a
    // node resource route.
  }
}
