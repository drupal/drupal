<?php

namespace Drupal\rest\Tests;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\node\NodeInterface;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test helper class that provides a REST client method to send HTTP requests.
 *
 * @deprecated in Drupal 8.3.x-dev and will be removed before Drupal 9.0.0. Use \Drupal\Tests\rest\Functional\ResourceTestBase and \Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase instead. Only retained for contributed module tests that may be using this base class.
 */
abstract class RESTTestBase extends WebTestBase {

  /**
   * The REST resource config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $resourceConfigStorage;

  /**
   * The default serialization format to use for testing REST operations.
   *
   * @var string
   */
  protected $defaultFormat;

  /**
   * The default MIME type to use for testing REST operations.
   *
   * @var string
   */
  protected $defaultMimeType;

  /**
   * The entity type to use for testing.
   *
   * @var string
   */
  protected $testEntityType = 'entity_test';

  /**
   * The default authentication provider to use for testing REST operations.
   *
   * @var array
   */
  protected $defaultAuth;


  /**
   * The raw response body from http request operations.
   *
   * @var array
   */
  protected $responseBody;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('rest', 'entity_test');

  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'hal_json';
    $this->defaultMimeType = 'application/hal+json';
    $this->defaultAuth = array('cookie');
    $this->resourceConfigStorage = $this->container->get('entity_type.manager')->getStorage('rest_resource_config');
    // Create a test content type for node testing.
    if (in_array('node', static::$modules)) {
      $this->drupalCreateContentType(array('name' => 'resttest', 'type' => 'resttest'));
    }
  }

  /**
   * Helper function to issue a HTTP request with simpletest's cURL.
   *
   * @param string|\Drupal\Core\Url $url
   *   A Url object or system path.
   * @param string $method
   *   HTTP method, one of GET, POST, PUT or DELETE.
   * @param string $body
   *   The body for POST and PUT.
   * @param string $mime_type
   *   The MIME type of the transmitted content.
   * @param bool $csrf_token
   *   If NULL, a CSRF token will be retrieved and used. If FALSE, omit the
   *   X-CSRF-Token request header (to simulate developer error). Otherwise, the
   *   passed in value will be used as the value for the X-CSRF-Token request
   *   header (to simulate developer error, by sending an invalid CSRF token).
   *
   * @return string
   *   The content returned from the request.
   */
  protected function httpRequest($url, $method, $body = NULL, $mime_type = NULL, $csrf_token = NULL) {
    if (!isset($mime_type)) {
      $mime_type = $this->defaultMimeType;
    }
    if (!in_array($method, array('GET', 'HEAD', 'OPTIONS', 'TRACE'))) {
      // GET the CSRF token first for writing requests.
      $requested_token = $this->drupalGet('session/token');
    }

    $url = $this->buildUrl($url);

    $curl_options = array();
    switch ($method) {
      case 'GET':
        // Set query if there are additional GET parameters.
        $curl_options = array(
          CURLOPT_HTTPGET => TRUE,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_URL => $url,
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('Accept: ' . $mime_type),
        );
        break;

      case 'HEAD':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'HEAD',
          CURLOPT_URL => $url,
          CURLOPT_NOBODY => TRUE,
          CURLOPT_HTTPHEADER => array('Accept: ' . $mime_type),
        );
        break;

      case 'POST':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $url,
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $csrf_token !== FALSE ? array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . ($csrf_token === NULL ? $requested_token : $csrf_token),
          ) : array(
            'Content-Type: ' . $mime_type,
          ),
        );
        break;

      case 'PUT':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $url,
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $csrf_token !== FALSE ? array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . ($csrf_token === NULL ? $requested_token : $csrf_token),
          ) : array(
            'Content-Type: ' . $mime_type,
          ),
        );
        break;

      case 'PATCH':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $url,
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $csrf_token !== FALSE ? array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . ($csrf_token === NULL ? $requested_token : $csrf_token),
          ) : array(
            'Content-Type: ' . $mime_type,
          ),
        );
        break;

      case 'DELETE':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_URL => $url,
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $csrf_token !== FALSE ? array(
            'X-CSRF-Token: ' . ($csrf_token === NULL ? $requested_token : $csrf_token),
          ) : array(),
        );
        break;
    }

    if ($mime_type === 'none') {
      unset($curl_options[CURLOPT_HTTPHEADER]['Content-Type']);
    }

    $this->responseBody = $this->curlExec($curl_options);

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    $headers = $this->drupalGetHeaders();

    $this->verbose($method . ' request to: ' . $url .
      '<hr />Code: ' . curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE) .
      (isset($curl_options[CURLOPT_HTTPHEADER]) ? '<hr />Request headers: ' . nl2br(print_r($curl_options[CURLOPT_HTTPHEADER], TRUE)) : '' ) .
      (isset($curl_options[CURLOPT_POSTFIELDS]) ? '<hr />Request body: ' . nl2br(print_r($curl_options[CURLOPT_POSTFIELDS], TRUE)) : '' ) .
      '<hr />Response headers: ' . nl2br(print_r($headers, TRUE)) .
      '<hr />Response body: ' . $this->responseBody);

    return $this->responseBody;
  }

  /**
   * Creates entity objects based on their types.
   *
   * @param string $entity_type
   *   The type of the entity that should be created.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The new entity object.
   */
  protected function entityCreate($entity_type) {
    return $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create($this->entityValues($entity_type));
  }

  /**
   * Provides an array of suitable property values for an entity type.
   *
   * Required properties differ from entity type to entity type, so we keep a
   * minimum mapping here.
   *
   * @param string $entity_type_id
   *   The ID of the type of entity that should be created.
   *
   * @return array
   *   An array of values keyed by property name.
   */
  protected function entityValues($entity_type_id) {
    switch ($entity_type_id) {
      case 'entity_test':
        return array(
          'name' => $this->randomMachineName(),
          'user_id' => 1,
          'field_test_text' => array(0 => array(
            'value' => $this->randomString(),
            'format' => 'plain_text',
          )),
        );
      case 'config_test':
        return [
          'id' => $this->randomMachineName(),
          'label' => 'Test label',
        ];
      case 'node':
        return array('title' => $this->randomString(), 'type' => 'resttest');
      case 'node_type':
        return array(
          'type' => 'article',
          'name' => $this->randomMachineName(),
        );
      case 'user':
        return array('name' => $this->randomMachineName());

      case 'comment':
        return [
          'subject' => $this->randomMachineName(),
          'entity_type' => 'node',
          'comment_type' => 'comment',
          'comment_body' => $this->randomString(),
          'entity_id' => 'invalid',
          'field_name' => 'comment',
        ];
      case 'taxonomy_vocabulary':
        return [
          'vid' => 'tags',
          'name' => $this->randomMachineName(),
        ];
      default:
        if ($this->isConfigEntity($entity_type_id)) {
          return $this->configEntityValues($entity_type_id);
        }
        return array();
    }
  }

  /**
   * Enables the REST service interface for a specific entity type.
   *
   * @param string|false $resource_type
   *   The resource type that should get REST API enabled or FALSE to disable all
   *   resource types.
   * @param string $method
   *   The HTTP method to enable, e.g. GET, POST etc.
   * @param string|array $format
   *   (Optional) The serialization format, e.g. hal_json, or a list of formats.
   * @param array $auth
   *   (Optional) The list of valid authentication methods.
   */
  protected function enableService($resource_type, $method = 'GET', $format = NULL, array $auth = []) {
    if ($resource_type) {
      // Enable REST API for this entity type.
      $resource_config_id = str_replace(':', '.', $resource_type);
      // get entity by id
      /** @var \Drupal\rest\RestResourceConfigInterface $resource_config */
      $resource_config = $this->resourceConfigStorage->load($resource_config_id);
      if (!$resource_config) {
        $resource_config = $this->resourceConfigStorage->create([
          'id' => $resource_config_id,
          'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
          'configuration' => []
        ]);
      }
      $configuration = $resource_config->get('configuration');

      if (is_array($format)) {
        for ($i = 0; $i < count($format); $i++) {
          $configuration[$method]['supported_formats'][] = $format[$i];
        }
      }
      else {
        if ($format == NULL) {
          $format = $this->defaultFormat;
        }
        $configuration[$method]['supported_formats'][] = $format;
      }

      if (!is_array($auth) || empty($auth)) {
        $auth = $this->defaultAuth;
      }
      foreach ($auth as $auth_provider) {
        $configuration[$method]['supported_auth'][] = $auth_provider;
      }

      $resource_config->set('configuration', $configuration);
      $resource_config->save();
    }
    else {
      foreach ($this->resourceConfigStorage->loadMultiple() as $resource_config) {
        $resource_config->delete();
      }
    }
    $this->rebuildCache();
  }

  /**
   * Rebuilds routing caches.
   */
  protected function rebuildCache() {
    // Rebuild routing cache, so that the REST API paths are available.
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * {@inheritdoc}
   *
   * This method is overridden to deal with a cURL quirk: the usage of
   * CURLOPT_CUSTOMREQUEST cannot be unset on the cURL handle, so we need to
   * override it every time it is omitted.
   */
  protected function curlExec($curl_options, $redirect = FALSE) {
    if (!isset($curl_options[CURLOPT_CUSTOMREQUEST])) {
      if (!empty($curl_options[CURLOPT_HTTPGET])) {
        $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
      }
      if (!empty($curl_options[CURLOPT_POST])) {
        $curl_options[CURLOPT_CUSTOMREQUEST] = 'POST';
      }
    }
    return parent::curlExec($curl_options, $redirect);
  }

  /**
   * Provides the necessary user permissions for entity operations.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $operation
   *   The operation, one of 'view', 'create', 'update' or 'delete'.
   *
   * @return array
   *   The set of user permission strings.
   */
  protected function entityPermissions($entity_type_id, $operation) {
    switch ($entity_type_id) {
      case 'entity_test':
        switch ($operation) {
          case 'view':
            return array('view test entity');
          case 'create':
          case 'update':
          case 'delete':
            return array('administer entity_test content');
        }
      case 'node':
        switch ($operation) {
          case 'view':
            return array('access content');
          case 'create':
            return array('create resttest content');
          case 'update':
            return array('edit any resttest content');
          case 'delete':
            return array('delete any resttest content');
        }

      case 'comment':
        switch ($operation) {
          case 'view':
            return ['access comments'];

          case 'create':
            return ['post comments', 'skip comment approval'];

          case 'update':
            return ['edit own comments'];

          case 'delete':
            return ['administer comments'];
        }
        break;

      case 'user':
        switch ($operation) {
          case 'view':
            return ['access user profiles'];

          default:
            return ['administer users'];
        }

      default:
        if ($this->isConfigEntity($entity_type_id)) {
          $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
          if ($admin_permission = $entity_type->getAdminPermission()) {
            return [$admin_permission];
          }
        }
    }
    return [];
  }

  /**
   * Loads an entity based on the location URL returned in the location header.
   *
   * @param string $location_url
   *   The URL returned in the Location header.
   *
   * @return \Drupal\Core\Entity\Entity|false
   *   The entity or FALSE if there is no matching entity.
   */
  protected function loadEntityFromLocationHeader($location_url) {
    $url_parts = explode('/', $location_url);
    $id = end($url_parts);
    return $this->container->get('entity_type.manager')
      ->getStorage($this->testEntityType)->load($id);
  }

  /**
   * Remove node fields that can only be written by an admin user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to remove fields where non-administrative users cannot write.
   *
   * @return \Drupal\node\NodeInterface
   *   The node with removed fields.
   */
  protected function removeNodeFieldsForNonAdminUsers(NodeInterface $node) {
    $node->set('status', NULL);
    $node->set('created', NULL);
    $node->set('changed', NULL);
    $node->set('promote', NULL);
    $node->set('sticky', NULL);
    $node->set('revision_timestamp', NULL);
    $node->set('revision_log', NULL);
    $node->set('uid', NULL);

    return $node;
  }

  /**
   * Check to see if the HTTP request response body is identical to the expected
   * value.
   *
   * @param $expected
   *   The first value to check.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertResponseBody($expected, $message = '', $group = 'REST Response') {
    return $this->assertIdentical($expected, $this->responseBody, $message ? $message : strtr('Response body @expected (expected) is equal to @response (actual).', array('@expected' => var_export($expected, TRUE), '@response' => var_export($this->responseBody, TRUE))), $group);
  }

  /**
   * Checks if an entity type id is for a Config Entity.
   *
   * @param string $entity_type_id
   *   The entity type ID to check.
   *
   * @return bool
   *   TRUE if the entity is a Config Entity, FALSE otherwise.
   */
  protected function isConfigEntity($entity_type_id) {
    return \Drupal::entityTypeManager()->getDefinition($entity_type_id) instanceof ConfigEntityType;
  }

  /**
   * Provides an array of suitable property values for a config entity type.
   *
   * Config entities have some common keys that need to be created. Required
   * properties differ among config entity types, so we keep a minimum mapping
   * here.
   *
   * @param string $entity_type_id
   *   The ID of the type of entity that should be created.
   *
   * @return array
   *   An array of values keyed by property name.
   */
  protected function configEntityValues($entity_type_id) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $keys = $entity_type->getKeys();
    $values = [];
    // Fill out known key values that are shared across entity types.
    foreach ($keys as $key) {
      if ($key === 'id' || $key === 'label') {
        $values[$key] = $this->randomMachineName();
      }
    }
    // Add extra values for particular entity types.
    switch ($entity_type_id) {
      case 'block':
        $values['plugin'] = 'system_powered_by_block';
        break;
    }
    return $values;
  }

}
