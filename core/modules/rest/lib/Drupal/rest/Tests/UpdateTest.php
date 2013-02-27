<?php

/**
 * @file
 * Contains Drupal\rest\test\UpdateTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests resource updates on test entities.
 */
class UpdateTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rest', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Update resource',
      'description' => 'Tests the update of resources.',
      'group' => 'REST',
    );
  }

  /**
   * Tests several valid and invalid partial update requests on test entities.
   */
  public function testPatchUpdate() {
    $serializer = drupal_container()->get('serializer');
    // @todo once EntityNG is implemented for other entity types test all other
    // entity types here as well.
    $entity_type = 'entity_test';

    $this->enableService('entity:' . $entity_type, 'PATCH');
    // Create a user account that has the required permissions to create
    // resources via the web API.
    $account = $this->drupalCreateUser(array('restful patch entity:' . $entity_type));
    $this->drupalLogin($account);

    // Create an entity and save it to the database.
    $entity = $this->entityCreate($entity_type);
    $entity->save();

    // Create a second stub entity for overwriting a field.
    $patch_values['field_test_text'] = array(0 => array('value' => $this->randomString()));
    $patch_entity = entity_create($entity_type, $patch_values);
    // We don't want to overwrite the UUID.
    unset($patch_entity->uuid);
    $serialized = $serializer->serialize($patch_entity, 'drupal_jsonld');

    // Update the entity over the web API.
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(204);

    // Re-load updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertEqual($entity->field_test_text->value, $patch_entity->field_test_text->value, 'Field was successfully updated.');

    // Try to empty a field.
    $normalized = $serializer->normalize($patch_entity, 'drupal_jsonld');
    $normalized['field_test_text'] = array();
    $serialized = $serializer->encode($normalized, 'jsonld');

    // Update the entity over the web API.
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(204);

    // Re-load updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertNull($entity->field_test_text->value, 'Test field has been cleared.');

    // Try to update a non-existing entity with ID 9999.
    $this->httpRequest('entity/' . $entity_type . '/9999', 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);
    $loaded_entity = entity_load($entity_type, 9999, TRUE);
    $this->assertFalse($loaded_entity, 'Entity 9999 was not created.');

    // Try to update an entity without proper permissions.
    $this->drupalLogout();
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(403);

    // Try to update a resource which is not web API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);
  }

  /**
   * Tests several valid and invalid PUT update requests on test entities.
   */
  public function testPutUpdate() {
    $serializer = drupal_container()->get('serializer');
    // @todo once EntityNG is implemented for other entity types test all other
    // entity types here as well.
    $entity_type = 'entity_test';

    $this->enableService('entity:' . $entity_type, 'PUT');
    // Create a user account that has the required permissions to create
    // resources via the web API.
    $account = $this->drupalCreateUser(array('restful put entity:' . $entity_type));
    $this->drupalLogin($account);

    // Create an entity and save it to the database.
    $entity = $this->entityCreate($entity_type);
    $entity->save();

    // Create a second entity that will overwrite the original.
    $update_values = $this->entityValues($entity_type);
    $update_entity = entity_create($entity_type, $update_values);
    // Copy the identifier properties over from the original.
    $update_entity->uuid->value = $entity->uuid();
    $update_entity->id->value = $entity->id();

    $serialized = $serializer->serialize($update_entity, 'drupal_jsonld');
    // Update the entity over the web API.
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PUT', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(204);

    // Re-load the updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    // @todo Don't check the user reference field for now until deserialization
    // for entity references is implemented.
    unset($update_values['user_id']);
    foreach ($update_values as $property => $value) {
      $update_value = $update_entity->{$property}->value;
      $stored_value = $entity->{$property}->value;
      $this->assertEqual($stored_value, $update_value, 'Updated property ' . $property . ' expected: ' . $update_value . ', actual: ' . $stored_value);
    }

    // Try to delete a property.
    unset($update_entity->field_test_text);
    $serialized = $serializer->serialize($update_entity, 'drupal_jsonld');
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PUT', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(204);

    // Re-load the updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertTrue($entity->field_test_text->isEmpty(), 'Property has been deleted.');

    // Try to create an entity with ID 9999.
    $this->httpRequest('entity/' . $entity_type . '/9999', 'PUT', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);
    $loaded_entity = entity_load($entity_type, 9999, TRUE);
    $this->assertFalse($loaded_entity, 'Entity 9999 was not created.');

    // Try to update an entity without proper permissions.
    $this->drupalLogout();
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PUT', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(403);

    // Try to update a resource which is not web API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PUT', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);
  }
}
