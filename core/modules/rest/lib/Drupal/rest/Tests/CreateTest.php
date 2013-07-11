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
  public static $modules = array('hal', 'rest', 'entity_test');

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

    $this->enableService('entity:' . $entity_type, 'POST');
    // Create a user account that has the required permissions to create
    // resources via the REST API.
    $permissions = $this->entityPermissions($entity_type, 'create');
    $permissions[] = 'restful post entity:' . $entity_type;
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entity_values = $this->entityValues($entity_type);
    $entity = entity_create($entity_type, $entity_values);
    $serialized = $serializer->serialize($entity, $this->defaultFormat);
    // Create the entity over the REST API.
    $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(201);

    // Get the new entity ID from the location header and try to read it from
    // the database.
    $location_url = $this->drupalGetHeader('location');
    $url_parts = explode('/', $location_url);
    $id = end($url_parts);
    $loaded_entity = entity_load($entity_type, $id);
    $this->assertNotIdentical(FALSE, $loaded_entity, 'The new ' . $entity_type . ' was found in the database.');
    $this->assertEqual($entity->uuid(), $loaded_entity->uuid(), 'UUID of created entity is correct.');
    // @todo Remove the user reference field for now until deserialization for
    // entity references is implemented.
    unset($entity_values['user_id']);
    foreach ($entity_values as $property => $value) {
      $actual_value = $loaded_entity->get($property)->value;
      $send_value = $entity->get($property)->value;
      $this->assertEqual($send_value, $actual_value, 'Created property ' . $property . ' expected: ' . $send_value . ', actual: ' . $actual_value);
    }

    $loaded_entity->delete();

    // Try to create an entity with an access protected field.
    // @see entity_test_entity_field_access()
    $entity->field_test_text->value = 'no access value';
    $serialized = $serializer->serialize($entity, $this->defaultFormat);
    $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);
    $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');

    // Restore the valid test value.
    $entity->field_test_text->value = $entity_values['field_test_text'][0]['value'];
    $serialized = $serializer->serialize($entity, $this->defaultFormat);

    // Try to send invalid data that cannot be correctly deserialized.
    $this->httpRequest('entity/' . $entity_type, 'POST', 'kaboom!', $this->defaultMimeType);
    $this->assertResponse(400);

    // Try to send no data at all, which does not make sense on POST requests.
    $this->httpRequest('entity/' . $entity_type, 'POST', NULL, $this->defaultMimeType);
    $this->assertResponse(400);

    // Try to create an entity without the CSRF token.
    $this->curlExec(array(
      CURLOPT_HTTPGET => FALSE,
      CURLOPT_POST => TRUE,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $serialized,
      CURLOPT_URL => url('entity/' . $entity_type, array('absolute' => TRUE)),
      CURLOPT_NOBODY => FALSE,
      CURLOPT_HTTPHEADER => array('Content-Type: ' . $this->defaultMimeType),
    ));
    $this->assertResponse(403);
    $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');

    // Try to create an entity without proper permissions.
    $this->drupalLogout();
    $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);
    $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');

    // Try to create a resource which is not REST API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest('entity/entity_test', 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(404);
    $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');

    // @todo Once EntityNG is implemented for other entity types add a security
    // test. It should not be possible for example to create a test entity on a
    // node resource route.
  }
}
