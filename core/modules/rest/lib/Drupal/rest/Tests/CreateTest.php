<?php

/**
 * @file
 * Contains \Drupal\rest\test\CreateTest.
 */

namespace Drupal\rest\Tests;

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
    $serializer = $this->container->get('serializer');
    $entity_types = array('entity_test', 'node');
    foreach ($entity_types as $entity_type) {

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
      if ($entity_type == 'entity_test') {
        $entity->field_test_text->value = 'no access value';
        $serialized = $serializer->serialize($entity, $this->defaultFormat);
        $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
        $this->assertResponse(403);
        $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');

        // Try to create a field with a text format this user has no access to.
        $entity->field_test_text->value = $entity_values['field_test_text'][0]['value'];
        $entity->field_test_text->format = 'full_html';
        $serialized = $serializer->serialize($entity, $this->defaultFormat);
        $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
        $this->assertResponse(422);
        $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');

        // Restore the valid test value.
        $entity->field_test_text->format = 'plain_text';
        $serialized = $serializer->serialize($entity, $this->defaultFormat);
      }

      // Try to send invalid data that cannot be correctly deserialized.
      $this->httpRequest('entity/' . $entity_type, 'POST', 'kaboom!', $this->defaultMimeType);
      $this->assertResponse(400);

      // Try to send no data at all, which does not make sense on POST requests.
      $this->httpRequest('entity/' . $entity_type, 'POST', NULL, $this->defaultMimeType);
      $this->assertResponse(400);

      // Try to send invalid data to trigger the entity validation constraints.
      // Send a UUID that is too long.
      $entity->set('uuid', $this->randomName(129));
      $invalid_serialized = $serializer->serialize($entity, $this->defaultFormat);
      $response = $this->httpRequest('entity/' . $entity_type, 'POST', $invalid_serialized, $this->defaultMimeType);
      $this->assertResponse(422);
      $error = drupal_json_decode($response);
      $this->assertEqual($error['error'], "Unprocessable Entity: validation failed.\nuuid.0.value: <em class=\"placeholder\">UUID</em>: may not be longer than 128 characters.\n");

      // Try to create an entity without proper permissions.
      $this->drupalLogout();
      $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
      $this->assertResponse(403);
      $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');
    }

    // Try to create a resource which is not REST API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest('entity/entity_test', 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(404);
    $this->assertFalse(entity_load_multiple($entity_type, NULL, TRUE), 'No entity has been created in the database.');

    // @todo Add a security test. It should not be possible for example to
    //   create a test entity on a node resource route.
  }

}
