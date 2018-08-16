<?php

namespace Drupal\rest\Tests;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\node\NodeInterface;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\simpletest\WebTestBase;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;

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
  public static $modules = ['rest', 'entity_test'];

  /**
   * The last response.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'hal_json';
    $this->defaultMimeType = 'application/hal+json';
    $this->defaultAuth = ['cookie'];
    $this->resourceConfigStorage = $this->container->get('entity_type.manager')->getStorage('rest_resource_config');
    // Create a test content type for node testing.
    if (in_array('node', static::$modules)) {
      $this->drupalCreateContentType(['name' => 'resttest', 'type' => 'resttest']);
    }

    $this->cookieFile = $this->publicFilesDirectory . '/cookie.jar';
  }

  /**
   * Calculates cookies used by guzzle later.
   *
   * @return \GuzzleHttp\Cookie\CookieJarInterface
   *   The used CURL options in guzzle.
   */
  protected function cookies() {
    $cookies = [];

    foreach ($this->cookies as $key => $cookie) {
      $cookies[$key][] = $cookie['value'];
    }

    $request = \Drupal::request();
    $cookies = NestedArray::mergeDeep($cookies, $this->extractCookiesFromRequest($request));

    $cookie_jar = new FileCookieJar($this->cookieFile);
    foreach ($cookies as $key => $cookie_values) {
      foreach ($cookie_values as $cookie_value) {
        // setcookie() sets the value of a cookie to be deleted, when its gonna
        // be removed.
        if ($cookie_value !== 'deleted') {
          $cookie_jar->setCookie(new SetCookie(['Name' => $key, 'Value' => $cookie_value, 'Domain' => $request->getHost()]));
        }
      }
    }

    return $cookie_jar;
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
    if (!in_array($method, ['GET', 'HEAD', 'OPTIONS', 'TRACE'])) {
      // GET the CSRF token first for writing requests.
      $requested_token = $this->drupalGet('session/token');
    }

    $client = \Drupal::httpClient();
    $url = $this->buildUrl($url);

    $options = [
      'http_errors' => FALSE,
      'cookies' => $this->cookies(),
      'curl' => [
        CURLOPT_HEADERFUNCTION => [&$this, 'curlHeaderCallback'],
      ],
    ];
    switch ($method) {
      case 'GET':
        $options += [
          'headers' => [
            'Accept' => $mime_type,
          ],
        ];
        $response = $client->get($url, $options);
        break;

      case 'HEAD':
        $response = $client->head($url, $options);
        break;

      case 'POST':
        $options += [
          'headers' => $csrf_token !== FALSE ? [
            'Content-Type' => $mime_type,
            'X-CSRF-Token' => ($csrf_token === NULL ? $requested_token : $csrf_token),
          ] : [
            'Content-Type' => $mime_type,
          ],
          'body' => $body,
        ];
        $response = $client->post($url, $options);
        break;

      case 'PUT':
        $options += [
          'headers' => $csrf_token !== FALSE ? [
            'Content-Type' => $mime_type,
            'X-CSRF-Token' => ($csrf_token === NULL ? $requested_token : $csrf_token),
          ] : [
            'Content-Type' => $mime_type,
          ],
          'body' => $body,
        ];
        $response = $client->put($url, $options);
        break;

      case 'PATCH':
        $options += [
          'headers' => $csrf_token !== FALSE ? [
            'Content-Type' => $mime_type,
            'X-CSRF-Token' => ($csrf_token === NULL ? $requested_token : $csrf_token),
          ] : [
            'Content-Type' => $mime_type,
          ],
          'body' => $body,
        ];
        $response = $client->patch($url, $options);
        break;

      case 'DELETE':
        $options += [
          'headers' => $csrf_token !== FALSE ? [
            'Content-Type' => $mime_type,
            'X-CSRF-Token' => ($csrf_token === NULL ? $requested_token : $csrf_token),
          ] : [],
        ];
        $response = $client->delete($url, $options);
        break;
    }

    $this->response = $response;
    $this->responseBody = (string) $response->getBody();
    $this->setRawContent($this->responseBody);

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    $this->verbose($method . ' request to: ' . $url .
      '<hr />Code: ' . $this->response->getStatusCode() .
      (isset($options['headers']) ? '<hr />Request headers: ' . nl2br(print_r($options['headers'], TRUE)) : '') .
      (isset($options['body']) ? '<hr />Request body: ' . nl2br(print_r($options['body'], TRUE)) : '') .
      '<hr />Response headers: ' . nl2br(print_r($response->getHeaders(), TRUE)) .
      '<hr />Response body: ' . $this->responseBody);

    return $this->responseBody;
  }

  /**
   * {@inheritdoc}
   */
  protected function assertResponse($code, $message = '', $group = 'Browser') {
    if (!isset($this->response)) {
      return parent::assertResponse($code, $message, $group);
    }
    return $this->assertEqual($code, $this->response->getStatusCode(), $message ? $message : "HTTP response expected $code, actual {$this->response->getStatusCode()}", $group);
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalGetHeaders($all_requests = FALSE) {
    if (!isset($this->response)) {
      return parent::drupalGetHeaders($all_requests);
    }
    $lowercased_keys = array_map('strtolower', array_keys($this->response->getHeaders()));
    return array_map(function (array $header) {
      return implode(', ', $header);
    }, array_combine($lowercased_keys, array_values($this->response->getHeaders())));
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalGetHeader($name, $all_requests = FALSE) {
    if (!isset($this->response)) {
      return parent::drupalGetHeader($name, $all_requests);
    }
    if ($header = $this->response->getHeader($name)) {
      return implode(', ', $header);
    }
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
        return [
          'name' => $this->randomMachineName(),
          'user_id' => 1,
          'field_test_text' => [
            0 => [
              'value' => $this->randomString(),
              'format' => 'plain_text',
            ],
          ],
        ];
      case 'config_test':
        return [
          'id' => $this->randomMachineName(),
          'label' => 'Test label',
        ];
      case 'node':
        return ['title' => $this->randomString(), 'type' => 'resttest'];
      case 'node_type':
        return [
          'type' => 'article',
          'name' => $this->randomMachineName(),
        ];
      case 'user':
        return ['name' => $this->randomMachineName()];

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
      case 'block':
        // Block placements depend on themes, ensure Bartik is installed.
        $this->container->get('theme_installer')->install(['bartik']);
        return [
          'id' => strtolower($this->randomMachineName(8)),
          'plugin' => 'system_powered_by_block',
          'theme' => 'bartik',
          'region' => 'header',
        ];
      default:
        if ($this->isConfigEntity($entity_type_id)) {
          return $this->configEntityValues($entity_type_id);
        }
        return [];
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
          'configuration' => [],
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
    $this->container->get('router.builder')->rebuildIfNeeded();
  }

  /**
   * {@inheritdoc}
   *
   * This method is overridden to deal with a cURL quirk: the usage of
   * CURLOPT_CUSTOMREQUEST cannot be unset on the cURL handle, so we need to
   * override it every time it is omitted.
   */
  protected function curlExec($curl_options, $redirect = FALSE) {
    unset($this->response);

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
            return ['view test entity'];
          case 'create':
          case 'update':
          case 'delete':
            return ['administer entity_test content'];
        }
      case 'node':
        switch ($operation) {
          case 'view':
            return ['access content'];
          case 'create':
            return ['create resttest content'];
          case 'update':
            return ['edit any resttest content'];
          case 'delete':
            return ['delete any resttest content'];
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
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
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
    return $this->assertIdentical($expected, $this->responseBody, $message ? $message : strtr('Response body @expected (expected) is equal to @response (actual).', ['@expected' => var_export($expected, TRUE), '@response' => var_export($this->responseBody, TRUE)]), $group);
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
