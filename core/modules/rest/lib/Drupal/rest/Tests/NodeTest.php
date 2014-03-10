<?php

/**
 * @file
 * Contains \Drupal\rest\Tests\NodeTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests resource read operations on test entities, nodes and users.
 */
class NodeTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * Ensure that the node resource works with comment module enabled.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Node resource',
      'description' => 'Test special cases for node entities.',
      'group' => 'REST',
    );
  }

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
   * Performs various tests on nodes and their REST API.
   */
  public function testNodes() {
    $this->enableNodeConfiguration('GET', 'view');

    $node = $this->entityCreate('node');
    $node->save();
    $this->httpRequest('entity/node/' . $node->id(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200);

    // Check that a simple PATCH update to the node title works as expected.
    $this->enableNodeConfiguration('PATCH', 'update');

    // Create a PATCH request body that only updates the title field.
    $new_title = $this->randomString();
    $data = array(
      '_links' => array(
        'type' => array(
          'href' => url('rest/type/node/resttest', array('absolute' => TRUE)),
        ),
      ),
      'title' => array(
        array(
          'value' => $new_title,
        ),
      ),
    );
    $serialized = $this->container->get('serializer')->serialize($data, $this->defaultFormat);
    $this->httpRequest('entity/node/' . $node->id(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    // Reload the node from the DB and check if the title was correctly updated.
    $updated_node = entity_load('node', $node->id(), TRUE);
    $this->assertEqual($updated_node->getTitle(), $new_title);
    // Make sure that the UUID of the node has not changed.
    $this->assertEqual($node->get('uuid')->getValue(), $updated_node->get('uuid')->getValue(), 'UUID was not changed.');
  }
}
