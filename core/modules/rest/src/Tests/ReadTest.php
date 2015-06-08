<?php

/**
 * @file
 * Definition of Drupal\rest\test\ReadTest.
 */

namespace Drupal\rest\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests the retrieval of resources.
 *
 * @group rest
 */
class ReadTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test');

  /**
   * Tests several valid and invalid read requests on all entity types.
   */
  public function testRead() {
    // @todo Expand this at least to users.
    // Define the entity types we want to test.
    $entity_types = array('entity_test', 'node');
    foreach ($entity_types as $entity_type) {
      $this->enableService('entity:' . $entity_type, 'GET');
      // Create a user account that has the required permissions to read
      // resources via the REST API.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get entity:' . $entity_type;
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // Create an entity programmatically.
      $entity = $this->entityCreate($entity_type);
      $entity->save();
      // Read it over the REST API.
      $response = $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET');
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('content-type', $this->defaultMimeType);
      $data = Json::decode($response);
      // Only assert one example property here, other properties should be
      // checked in serialization tests.
      $this->assertEqual($data['uuid'][0]['value'], $entity->uuid(), 'Entity UUID is correct');

      // Try to read the entity with an unsupported mime format.
      $response = $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', 'wrongformat'), 'GET');
      $this->assertResponse(406);
      $this->assertHeader('Content-type', 'application/json');

      // Try to read an entity that does not exist.
      $response = $this->httpRequest(Url::fromUri('base://' . $entity_type . '/9999', ['query' => ['_format' => $this->defaultFormat]]), 'GET');
      $this->assertResponse(404);
      $path = $entity_type == 'node' ? '/node/{node}' : '/entity_test/{entity_test}';
      $expected_message = Json::encode(['message' => 'The "' . $entity_type . '" parameter was not converted for the path "' . $path . '" (route name: "rest.entity.' . $entity_type . '.GET.hal_json")']);
      $this->assertIdentical($expected_message, $response, 'Response message is correct.');

      // Make sure that field level access works and that the according field is
      // not available in the response. Only applies to entity_test.
      // @see entity_test_entity_field_access()
      if ($entity_type == 'entity_test') {
        $entity->field_test_text->value = 'no access value';
        $entity->save();
        $response = $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET');
        $this->assertResponse(200);
        $this->assertHeader('content-type', $this->defaultMimeType);
        $data = Json::decode($response);
        $this->assertFalse(isset($data['field_test_text']), 'Field access protected field is not visible in the response.');
      }

      // Try to read an entity without proper permissions.
      $this->drupalLogout();
      $response = $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET');
      $this->assertResponse(403);
      $this->assertIdentical('{"message":""}', $response);
    }
    // Try to read a resource which is not REST API enabled.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $response = $this->httpRequest($account->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET');
    // \Drupal\Core\Routing\RequestFormatRouteFilter considers the canonical,
    // non-REST route a match, but a lower quality one: no format restrictions
    // means there's always a match and hence when there is no matching REST
    // route, the non-REST route is used, but can't render into
    // application/hal+json, so it returns a 406.
    $this->assertResponse('406', 'HTTP response code is 406 when the resource does not define formats, because it falls back to the canonical, non-REST route.');
    $this->assertEqual($response, Json::encode([
      'message' => 'Not acceptable',
    ]));
  }

  /**
   * Tests the resource structure.
   */
  public function testResourceStructure() {
    // Enable a service with a format restriction but no authentication.
    $this->enableService('entity:node', 'GET', 'json');
    // Create a user account that has the required permissions to read
    // resources via the REST API.
    $permissions = $this->entityPermissions('node', 'view');
    $permissions[] = 'restful get entity:node';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Create an entity programmatically.
    $entity = $this->entityCreate('node');
    $entity->save();

    // Read it over the REST API.
    $response = $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', 'json'), 'GET');
    $this->assertResponse('200', 'HTTP response code is correct.');
  }

}
