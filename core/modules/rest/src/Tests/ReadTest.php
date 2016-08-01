<?php

namespace Drupal\rest\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

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
  public static $modules = [
    'hal',
    'rest',
    'node',
    'entity_test',
    'config_test',
    'taxonomy',
    'block',
  ];

  /**
   * Tests several valid and invalid read requests on all entity types.
   */
  public function testRead() {
    // @todo Expand this at least to users.
    // Define the entity types we want to test.
    $entity_types = [
      'entity_test',
      'node',
      'config_test',
      'taxonomy_vocabulary',
      'block',
      'user_role',
    ];
    foreach ($entity_types as $entity_type) {
      $this->enableService('entity:' . $entity_type, 'GET');
      // Create a user account that has the required permissions to read
      // resources via the REST API.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // Create an entity programmatically.
      $entity = $this->entityCreate($entity_type);
      $entity->save();

      // Verify that it exists: use a HEAD request.
      $this->httpRequest($this->getReadUrl($entity), 'HEAD');
      $this->assertResponseBody('');
      $head_headers = $this->drupalGetHeaders();

      // Read it over the REST API.
      $response = $this->httpRequest($this->getReadUrl($entity), 'GET');
      $get_headers = $this->drupalGetHeaders();
      $this->assertResponse('200', 'HTTP response code is correct.');

      // Verify that the GET and HEAD responses are the same, that the only
      // difference is that there's no body.
      unset($get_headers['date']);
      unset($head_headers['date']);
      unset($get_headers['content-length']);
      unset($head_headers['content-length']);
      unset($get_headers['x-drupal-dynamic-cache']);
      unset($head_headers['x-drupal-dynamic-cache']);
      $this->assertIdentical($get_headers, $head_headers);
      $this->assertResponse('200', 'HTTP response code is correct.');

      $this->assertHeader('content-type', $this->defaultMimeType);
      $data = Json::decode($response);
      // Only assert one example property here, other properties should be
      // checked in serialization tests.
      if ($entity instanceof ConfigEntityInterface) {
        $this->assertEqual($data['uuid'], $entity->uuid(), 'Entity UUID is correct');
      }
      else {
        $this->assertEqual($data['uuid'][0]['value'], $entity->uuid(), 'Entity UUID is correct');
      }

      // Try to read the entity with an unsupported mime format.
      $this->httpRequest($this->getReadUrl($entity, 'wrongformat'), 'GET');
      $this->assertResponse(406);
      $this->assertHeader('Content-type', 'application/json');

      // Try to read an entity that does not exist.
      $response = $this->httpRequest($this->getReadUrl($entity, $this->defaultFormat, 9999), 'GET');
      $this->assertResponse(404);
      switch ($entity_type) {
        case 'node':
          $path = '/node/{node}';
          break;

        case 'entity_test':
          $path = '/entity_test/{entity_test}';
          break;

        default:
          $path = "/entity/$entity_type/{" . $entity_type . '}';
      }
      $expected_message = Json::encode(['message' => 'The "' . $entity_type . '" parameter was not converted for the path "' . $path . '" (route name: "rest.entity.' . $entity_type . '.GET.hal_json")']);
      $this->assertIdentical($expected_message, $response, 'Response message is correct.');

      // Make sure that field level access works and that the according field is
      // not available in the response. Only applies to entity_test.
      // @see entity_test_entity_field_access()
      if ($entity_type == 'entity_test') {
        $entity->field_test_text->value = 'no access value';
        $entity->save();
        $response = $this->httpRequest($this->getReadUrl($entity), 'GET');
        $this->assertResponse(200);
        $this->assertHeader('content-type', $this->defaultMimeType);
        $data = Json::decode($response);
        $this->assertFalse(isset($data['field_test_text']), 'Field access protected field is not visible in the response.');
      }
    }
    // Try to read a resource, the user entity, which is not REST API enabled.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $response = $this->httpRequest($this->getReadUrl($account), 'GET');

    // \Drupal\Core\Routing\RequestFormatRouteFilter considers the canonical,
    // non-REST route a match, but a lower quality one: no format restrictions
    // means there's always a match and hence when there is no matching REST
    // route, the non-REST route is used, but can't render into
    // application/hal+json, so it returns a 406.
    $this->assertResponse('406', 'HTTP response code is 406 when the resource does not define formats, because it falls back to the canonical, non-REST route.');
    $this->assertEqual($response, Json::encode([
      'message' => 'Not acceptable format: hal_json',
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
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Create an entity programmatically.
    $entity = $this->entityCreate('node');
    $entity->save();

    // Read it over the REST API.
    $this->httpRequest($this->getReadUrl($entity, 'json'), 'GET');
    $this->assertResponse('200', 'HTTP response code is correct.');
  }

  /**
   * Gets the read URL object for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the URL for.
   * @param string $format
   *   The format to request the entity in.
   * @param string $entity_id
   *   The entity ID to use in the URL, defaults to the entity's ID if know
   *   given.
   *
   * @return \Drupal\Core\Url
   *   The Url object.
   */
  protected function getReadUrl(EntityInterface $entity, $format = NULL, $entity_id = NULL) {
    if (!$format) {
      $format = $this->defaultFormat;
    }
    if (!$entity_id) {
      $entity_id = $entity->id();
    }
    $entity_type = $entity->getEntityTypeId();
    if ($entity->hasLinkTemplate('canonical')) {
      $url = $entity->toUrl('canonical');
    }
    else {
      $route_name = 'rest.entity.' . $entity_type . ".GET.";
      // If testing unsupported format don't use the format to construct route
      // name. This would give a RouteNotFoundException.
      if ($format == 'wrongformat') {
        $route_name .= $this->defaultFormat;
      }
      else {
        $route_name .= $format;
      }
      $url = Url::fromRoute($route_name);
    }
    $url->setRouteParameter($entity_type, $entity_id);
    $url->setRouteParameter('_format', $format);
    return $url;
  }

}
