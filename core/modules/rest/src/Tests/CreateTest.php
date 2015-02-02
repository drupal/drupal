<?php

/**
 * @file
 * Contains \Drupal\rest\test\CreateTest.
 */

namespace Drupal\rest\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Tests the creation of resources.
 *
 * @group rest
 */
class CreateTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test');

  /**
   * The 'serializer' service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  protected function setUp() {
    parent::setUp();
    // Get the 'serializer' service.
    $this->serializer = $this->container->get('serializer');
  }

  /**
   * Try to create a resource which is not REST API enabled.
   */
  public function testCreateResourceRestApiNotEnabled() {
    $entity_type = 'entity_test';
    // Enables the REST service for a specific entity type.
    $this->enableService('entity:' . $entity_type, 'POST');

    // Get the necessary user permissions to create the current entity type.
    $permissions = $this->entityPermissions($entity_type, 'create');
    // POST method must be allowed for the current entity type.
    $permissions[] = 'restful post entity:' . $entity_type;

    // Create the user.
    $account = $this->drupalCreateUser($permissions);
    // Populate some entity properties before create the entity.
    $entity_values = $this->entityValues($entity_type);
    $entity = EntityTest::create($entity_values);

    // Serialize the entity before the POST request.
    $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);

    // Disable all resource types.
    $this->enableService(FALSE);
    $this->drupalLogin($account);

    // POST request to create the current entity. GET request for CSRF token
    // is included into the httpRequest() method.
    $this->httpRequest('entity/entity_test', 'POST', $serialized, $this->defaultMimeType);

    // The resource is not enabled. So, we receive a 'not found' response.
    $this->assertResponse(404);
    $this->assertFalse(EntityTest::loadMultiple(), 'No entity has been created in the database.');
  }

  /**
   * Tests several valid and invalid create requests for 'entity_test' entity type.
   */
  public function testCreateEntityTest() {
    $entity_type = 'entity_test';
    // Enables the REST service for 'entity_test' entity type.
    $this->enableService('entity:' . $entity_type, 'POST');
    // Create two accounts that have the required permissions to create resources.
    // The second one has administrative permissions.
    $accounts = $this->createAccountPerEntity($entity_type);

    // Verify create requests per user.
    foreach ($accounts as $key => $account) {
      $this->drupalLogin($account);
      // Populate some entity properties before create the entity.
      $entity_values = $this->entityValues($entity_type);
      $entity = EntityTest::create($entity_values);

      // Serialize the entity before the POST request.
      $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);

      // Create the entity over the REST API.
      $this->assertCreateEntityOverRestApi($entity_type, $serialized);
      // Get the entity ID from the location header and try to read it from the database.
      $this->assertReadEntityIdFromHeaderAndDb($entity_type, $entity, $entity_values);

      // Try to create an entity with an access protected field.
      // @see entity_test_entity_field_access()
      $normalized = $this->serializer->normalize($entity, $this->defaultFormat, ['account' => $account]);
      $normalized['field_test_text'][0]['value'] = 'no access value';
      $this->httpRequest('entity/' . $entity_type, 'POST', $this->serializer->serialize($normalized, $this->defaultFormat, ['account' => $account]), $this->defaultMimeType);
      $this->assertResponse(403);
      $this->assertFalse(EntityTest::loadMultiple(), 'No entity has been created in the database.');

      // Try to create a field with a text format this user has no access to.
      $entity->field_test_text->value = $entity_values['field_test_text'][0]['value'];
      $entity->field_test_text->format = 'full_html';

      $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);
      $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
      // The value selected is not a valid choice because the format must be 'plain_txt'.
      $this->assertResponse(422);
      $this->assertFalse(EntityTest::loadMultiple(), 'No entity has been created in the database.');

      // Restore the valid test value.
      $entity->field_test_text->format = 'plain_text';
      $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);

      // Try to send invalid data that cannot be correctly deserialized.
      $this->assertCreateEntityInvalidData($entity_type);

      // Try to send no data at all, which does not make sense on POST requests.
      $this->assertCreateEntityNoData($entity_type);

      // Try to send invalid data to trigger the entity validation constraints. Send a UUID that is too long.
      $this->assertCreateEntityInvalidSerialized($entity, $entity_type);

      // Try to create an entity without proper permissions.
      $this->assertCreateEntityWithoutProperPermissions($entity_type, $serialized, ['account' => $account]);

    }

  }

  /**
   * Tests several valid and invalid create requests for 'node' entity type.
   */
  public function testCreateNode() {
    $entity_type = 'node';
    // Enables the REST service for 'node' entity type.
    $this->enableService('entity:' . $entity_type, 'POST');
    // Create two accounts that have the required permissions to create resources.
    // The second one has administrative permissions.
    $accounts = $this->createAccountPerEntity($entity_type);

    // Verify create requests per user.
    foreach ($accounts as $key => $account) {
      $this->drupalLogin($account);
      // Populate some entity properties before create the entity.
      $entity_values = $this->entityValues($entity_type);
      $entity = Node::create($entity_values);

      // Verify that user cannot create content when trying to write to fields where it is not possible.
      if (!$account->hasPermission('administer nodes')) {
        $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);
        $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
        $this->assertResponse(403);
        // Remove fields where non-administrative users cannot write.
        $entity = $this->removeNodeFieldsForNonAdminUsers($entity);
      }
      else {
        // Changed and revision_timestamp fields can never be added.
        unset($entity->changed);
        unset($entity->revision_timestamp);
      }

      $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);

      // Create the entity over the REST API.
      $this->assertCreateEntityOverRestApi($entity_type, $serialized);

      // Get the new entity ID from the location header and try to read it from the database.
      $this->assertReadEntityIdFromHeaderAndDb($entity_type, $entity, $entity_values);

      // Try to send invalid data that cannot be correctly deserialized.
      $this->assertCreateEntityInvalidData($entity_type);

      // Try to send no data at all, which does not make sense on POST requests.
      $this->assertCreateEntityNoData($entity_type);

      // Try to send invalid data to trigger the entity validation constraints. Send a UUID that is too long.
      $this->assertCreateEntityInvalidSerialized($entity, $entity_type);

      // Try to create an entity without proper permissions.
      $this->assertCreateEntityWithoutProperPermissions($entity_type, $serialized);

    }

  }

  /**
   * Tests several valid and invalid create requests for 'user' entity type.
   */
  public function testCreateUser() {
    $entity_type = 'user';
    // Enables the REST service for 'user' entity type.
    $this->enableService('entity:' . $entity_type, 'POST');
    // Create two accounts that have the required permissions to create resources.
    // The second one has administrative permissions.
    $accounts = $this->createAccountPerEntity($entity_type);

    foreach ($accounts as $key => $account) {
      $this->drupalLogin($account);
      $entity_values = $this->entityValues($entity_type);
      $entity = User::create($entity_values);

      // Verify that only administrative users can create users.
      if (!$account->hasPermission('administer users')) {
        $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);
        $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
        $this->assertResponse(403);
        continue;
      }

      // Changed field can never be added.
      unset($entity->changed);

      $serialized = $this->serializer->serialize($entity, $this->defaultFormat, ['account' => $account]);

      // Create the entity over the REST API.
      $this->assertCreateEntityOverRestApi($entity_type, $serialized);

      // Get the new entity ID from the location header and try to read it from the database.
      $this->assertReadEntityIdFromHeaderAndDb($entity_type, $entity, $entity_values);

      // Try to send invalid data that cannot be correctly deserialized.
      $this->assertCreateEntityInvalidData($entity_type);

      // Try to send no data at all, which does not make sense on POST requests.
      $this->assertCreateEntityNoData($entity_type);

      // Try to send invalid data to trigger the entity validation constraints.
      // Send a UUID that is too long.
      $this->assertCreateEntityInvalidSerialized($entity, $entity_type);
    }

  }

  /**
   * Creates user accounts that have the required permissions to create resources via the REST API.
   * The second one has administrative permissions.
   *
   * @param string $entity_type
   *   Entity type needed to apply user permissions.
   * @return array
   *   An array that contains user accounts.
   */
  public function createAccountPerEntity($entity_type) {
    $accounts = array();
    // Get the necessary user permissions for the current $entity_type creation.
    $permissions = $this->entityPermissions($entity_type, 'create');
    // POST method must be allowed for the current entity type.
    $permissions[] = 'restful post entity:' . $entity_type;
    // Create user without administrative permissions.
    $accounts[] = $this->drupalCreateUser($permissions);
    // Add administrative permissions for nodes and users.
    $permissions[] = 'administer nodes';
    $permissions[] = 'administer users';
    // Create an administrative user.
    $accounts[] = $this->drupalCreateUser($permissions);

    return $accounts;
  }

  /**
   * Creates the entity over the REST API.
   *
   * @param string $entity_type
   *   The type of the entity that should be created.
   * @param string $serialized
   *   The body for the POST request.
   */
  public function assertCreateEntityOverRestApi($entity_type, $serialized = NULL) {
    $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(201);
  }

  /**
   * Get the new entity ID from the location header and try to read it from the database.
   *
   * @param string $entity_type
   *   Entity type we need to load the entity from DB.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we want to check that was inserted correctly.
   * @param array $entity_values
   *   The values of $entity.
   */
  public function assertReadEntityIdFromHeaderAndDb($entity_type, EntityInterface $entity, array $entity_values = array()) {
    // Get the location from the HTTP response header.
    $location_url = $this->drupalGetHeader('location');
    $url_parts = explode('/', $location_url);
    $id = end($url_parts);

    // Get the entity using the ID found.
    $loaded_entity = \Drupal::entityManager()->getStorage($entity_type)->load($id);
    $this->assertNotIdentical(FALSE, $loaded_entity, 'The new ' . $entity_type . ' was found in the database.');
    $this->assertEqual($entity->uuid(), $loaded_entity->uuid(), 'UUID of created entity is correct.');

    // Verify that the field values sent and received from DB are the same.
    foreach ($entity_values as $property => $value) {
      $actual_value = $loaded_entity->get($property)->value;
      $send_value = $entity->get($property)->value;
      $this->assertEqual($send_value, $actual_value, 'Created property ' . $property . ' expected: ' . $send_value . ', actual: ' . $actual_value);
    }

    // Delete the entity loaded from DB.
    $loaded_entity->delete();
  }

  /**
   * Try to send invalid data that cannot be correctly deserialized.
   *
   * @param string $entity_type
   *   The type of the entity that should be created.
   */
  public function assertCreateEntityInvalidData($entity_type) {
    $this->httpRequest('entity/' . $entity_type, 'POST', 'kaboom!', $this->defaultMimeType);
    $this->assertResponse(400);
  }

  /**
   * Try to send no data at all, which does not make sense on POST requests.
   *
   * @param string $entity_type
   *   The type of the entity that should be created.
   */
  public function assertCreateEntityNoData($entity_type) {
    $this->httpRequest('entity/' . $entity_type, 'POST', NULL, $this->defaultMimeType);
    $this->assertResponse(400);
  }

  /**
   * Send an invalid UUID to trigger the entity validation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we want to check that was inserted correctly.
   * @param string $entity_type
   *   The type of the entity that should be created.
   * @param array $context
   *   Options normalizers/encoders have access to.
   */
  public function assertCreateEntityInvalidSerialized(EntityInterface $entity, $entity_type, array $context = array()) {
    // Add a UUID that is too long.
    $entity->set('uuid', $this->randomMachineName(129));
    $invalid_serialized = $this->serializer->serialize($entity, $this->defaultFormat, $context);

    $response = $this->httpRequest('entity/' . $entity_type, 'POST', $invalid_serialized, $this->defaultMimeType);

    // Unprocessable Entity as response.
    $this->assertResponse(422);

    // Verify that the text of the response is correct.
    $error = Json::decode($response);
    $this->assertEqual($error['error'], "Unprocessable Entity: validation failed.\nuuid.0.value: <em class=\"placeholder\">UUID</em>: may not be longer than 128 characters.\n");
  }

  /**
   * Try to create an entity without proper permissions.
   *
   * @param string $entity_type
   *   The type of the entity that should be created.
   * @param string $serialized
   *   The body for the POST request.
   */
  public function assertCreateEntityWithoutProperPermissions($entity_type, $serialized = NULL) {
    $this->drupalLogout();
    $this->httpRequest('entity/' . $entity_type, 'POST', $serialized, $this->defaultMimeType);
    // Forbidden Error as response.
    $this->assertResponse(403);
    $this->assertFalse(\Drupal::entityManager()->getStorage($entity_type)->loadMultiple(), 'No entity has been created in the database.');
  }

}
