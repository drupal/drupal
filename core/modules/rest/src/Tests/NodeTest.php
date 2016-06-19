<?php

namespace Drupal\rest\Tests;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Tests special cases for node entities.
 *
 * @group rest
 */
class NodeTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * Ensure that the node resource works with comment module enabled.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'comment', 'node');

  /**
   * Enables node specific REST API configuration and authentication.
   *
   * @param string $method
   *   The HTTP method to be tested.
   * @param string $operation
   *   The operation, one of 'view', 'create', 'update' or 'delete'.
   */
  protected function enableNodeConfiguration($method, $operation) {
    $this->enableService('entity:node', $method);
    $permissions = $this->entityPermissions('node', $operation);
    $permissions[] = 'restful ' . strtolower($method) . ' entity:node';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
  }

  /**
   * Serializes and attempts to create a node via a REST "post" http request.
   *
   * @param array $data
   */
  protected function postNode($data) {
    // Enable node creation via POST.
    $this->enableNodeConfiguration('POST', 'create');
    $this->enableService('entity:node', 'POST', 'json');

    // Create a JSON version of a simple node with the title.
    $serialized = $this->container->get('serializer')->serialize($data, 'json');

    // Post to the REST service to create the node.
    $this->httpRequest('/entity/node', 'POST', $serialized, 'application/json');
  }

  /**
   * Tests the title on a newly created node.
   *
   * @param array $data
   * @return \Drupal\node\Entity\Node
   */
  protected function assertNodeTitleMatch($data) {
    /** @var \Drupal\node\Entity\Node $node */
    // Load the newly created node.
    $node = Node::load(1);

    // Test that the title is the same as what we posted.
    $this->assertEqual($node->title->value, $data['title'][0]['value']);

    return $node;
  }

  /**
   * Performs various tests on nodes and their REST API.
   */
  public function testNodes() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $this->enableNodeConfiguration('GET', 'view');

    $node = $this->entityCreate('node');
    $node->save();
    $this->httpRequest($node->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET');
    $this->assertResponse(200);
    $this->assertHeader('Content-type', $this->defaultMimeType);

    // Also check that JSON works and the routing system selects the correct
    // REST route.
    $this->enableService('entity:node', 'GET', 'json');
    $this->httpRequest($node->urlInfo()->setRouteParameter('_format', 'json'), 'GET');
    $this->assertResponse(200);
    $this->assertHeader('Content-type', 'application/json');

    // Check that a simple PATCH update to the node title works as expected.
    $this->enableNodeConfiguration('PATCH', 'update');

    // Create a PATCH request body that only updates the title field.
    $new_title = $this->randomString();
    $data = array(
      '_links' => array(
        'type' => array(
          'href' => Url::fromUri('base:rest/type/node/resttest', array('absolute' => TRUE))->toString(),
        ),
      ),
      'title' => array(
        array(
          'value' => $new_title,
        ),
      ),
    );
    $serialized = $this->container->get('serializer')->serialize($data, $this->defaultFormat);
    $this->httpRequest($node->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    // Reload the node from the DB and check if the title was correctly updated.
    $node_storage->resetCache(array($node->id()));
    $updated_node = $node_storage->load($node->id());
    $this->assertEqual($updated_node->getTitle(), $new_title);
    // Make sure that the UUID of the node has not changed.
    $this->assertEqual($node->get('uuid')->getValue(), $updated_node->get('uuid')->getValue(), 'UUID was not changed.');
  }

  /**
   * Test creating a node using json serialization.
   */
  public function testCreate() {
    // Data to be used for serialization.
    $data = [
      'type' => [['target_id' => 'resttest']],
      'title' => [['value' => $this->randomString() ]],
    ];

    $this->postNode($data);

    // Make sure the response is "CREATED".
    $this->assertResponse(201);

    // Make sure the node was created and the title matches.
    $node = $this->assertNodeTitleMatch($data);

    // Make sure the request returned a redirect header to view the node.
    $this->assertHeader('Location', $node->url('canonical', ['absolute' => TRUE]));
  }

  /**
   * Test bundle normalization when posting bundle as a simple string.
   */
  public function testBundleNormalization() {
    // Data to be used for serialization.
    $data = [
      'type' => 'resttest',
      'title' => [['value' => $this->randomString() ]],
    ];

    $this->postNode($data);

    // Make sure the response is "CREATED".
    $this->assertResponse(201);

    // Make sure the node was created and the title matches.
    $this->assertNodeTitleMatch($data);
  }

  /**
   * Test bundle normalization when posting using a simple string.
   */
  public function testInvalidBundle() {
    // Data to be used for serialization.
    $data = [
      'type' => 'bad_bundle_name',
      'title' => [['value' => $this->randomString() ]],
    ];

    $this->postNode($data);

    // Make sure the response is "Bad Request".
    $this->assertResponse(400);
    $this->assertResponseBody('{"error":"\"bad_bundle_name\" is not a valid bundle type for denormalization."}');
  }

  /**
   * Test when the bundle is missing.
   */
  public function testMissingBundle() {
    // Data to be used for serialization.
    $data = [
      'title' => [['value' => $this->randomString() ]],
    ];

    // testing
    $this->postNode($data);

    // Make sure the response is "Bad Request".
    $this->assertResponse(400);
    $this->assertResponseBody('{"error":"A string must be provided as a bundle value."}');
  }

}
