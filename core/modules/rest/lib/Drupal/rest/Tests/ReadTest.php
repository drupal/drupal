<?php

/**
 * @file
 * Definition of Drupal\rest\test\ReadTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests resource read operations on test entities, nodes and users.
 */
class ReadTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('jsonld', 'rest', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Read resource',
      'description' => 'Tests the retrieval of resources.',
      'group' => 'REST',
    );
  }

  /**
   * Tests several valid and invalid read requests on all entity types.
   */
  public function testRead() {
    // @todo once EntityNG is implemented for other entity types expand this at
    // least to nodes and users.
    // Define the entity types we want to test.
    $entity_types = array('entity_test');
    foreach ($entity_types as $entity_type) {
      $this->enableService('entity:' . $entity_type);
      // Create a user account that has the required permissions to delete
      // resources via the web API.
      $account = $this->drupalCreateUser(array('restful get entity:' . $entity_type));
      // Reset cURL here because it is confused from our previously used cURL
      // options.
      unset($this->curlHandle);
      $this->drupalLogin($account);

      // Create an entity programmatically.
      $entity = $this->entityCreate($entity_type);
      $entity->save();
      // Read it over the web API.
      $response = $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'GET', NULL, 'application/vnd.drupal.ld+json');
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('content-type', 'application/vnd.drupal.ld+json');
      $data = drupal_json_decode($response);
      // Only assert one example property here, other properties should be
      // checked in serialization tests.
      $this->assertEqual($data['uuid'][LANGUAGE_DEFAULT][0]['value'], $entity->uuid(), 'Entity UUID is correct');

      // Try to read the entity with an unsupported mime format.
      // Because the matcher checks mime type first, then method, this will hit
      // zero viable routes on the method.  If the mime matcher wasn't working,
      // we would still find an existing GET route with the wrong format. That
      // means this is a valid functional test for mime-matching.
      $response = $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'GET', NULL, 'application/wrongformat');
      $this->assertResponse(405);

      // Try to read an entity that does not exist.
      $response = $this->httpRequest('entity/' . $entity_type . '/9999', 'GET', NULL, 'application/vnd.drupal.ld+json');
      $this->assertResponse(404);
      $this->assertEqual($response, 'Entity with ID 9999 not found', 'Response message is correct.');

      // Try to read an entity without proper permissions.
      $this->drupalLogout();
      $response = $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'GET', NULL, 'application/vnd.drupal.ld+json');
      $this->assertResponse(403);
      $this->assertNull(drupal_json_decode($response), 'No valid JSON found.');
    }
    // Try to read a resource which is not web API enabled.
    $account = $this->drupalCreateUser();
    // Reset cURL here because it is confused from our previously used cURL
    // options.
    unset($this->curlHandle);
    $this->drupalLogin($account);
    $response = $this->httpRequest('entity/user/' . $account->id(), 'GET', NULL, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);
    $this->assertNull(drupal_json_decode($response), 'No valid JSON found.');
  }
}
