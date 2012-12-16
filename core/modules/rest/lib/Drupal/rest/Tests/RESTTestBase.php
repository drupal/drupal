<?php

/**
 * @file
 * Definition of Drupal\rest\test\RESTTestBase.
 */

namespace Drupal\rest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test helper class that provides a REST client method to send HTTP requests.
 */
abstract class RESTTestBase extends WebTestBase {

  /**
   * Stores HTTP response headers from the last HTTP request.
   *
   * @var array
   */
  protected $responseHeaders;

  /**
   * Helper function to issue a HTTP request with simpletest's cURL.
   *
   * @param string $url
   *   The relative URL, e.g. "entity/node/1"
   * @param string $method
   *   HTTP method, one of GET, POST, PUT or DELETE.
   * @param array $body
   *   Either the body for POST and PUT or additional URL parameters for GET.
   * @param string $format
   *   The MIME type of the transmitted content.
   */
  protected function httpRequest($url, $method, $body = NULL, $format = 'application/ld+json') {
    switch ($method) {
      case 'GET':
        // Set query if there are additional GET parameters.
        $options = isset($body) ? array('absolute' => TRUE, 'query' => $body) : array('absolute' => TRUE);
        $curl_options = array(
          CURLOPT_HTTPGET => TRUE,
          CURLOPT_URL => url($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('Accept: ' . $format),
        );
        break;

      case 'POST':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('Content-Type: ' . $format),
        );
        break;

      case 'PUT':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('Content-Type: ' . $format),
        );
        break;

      case 'DELETE':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
        );
        break;
    }
    // Include all HTTP headers in the response.
    $curl_options[CURLOPT_HEADER] = TRUE;

    $response = $this->curlExec($curl_options);

    list($header, $body) = explode("\r\n\r\n", $response, 2);
    $header_lines = explode("\r\n", $header);
    foreach ($header_lines as $line) {
      $parts = explode(':', $line, 2);
      // Store the header keys lower cased to be more robust. Headers are case
      // insensitive according to RFC 2616.
      $this->responseHeaders[strtolower($parts[0])] = isset($parts[1]) ? trim($parts[1]) : '';
    }

    $this->verbose($method . ' request to: ' . $url .
      '<hr />Code: ' . curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE) .
      '<hr />Response headers: ' . $header .
      '<hr />Response body: ' . $body);

    return $body;
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
    return entity_create($entity_type, $this->entityValues($entity_type));
  }

  /**
   * Provides an array of suitable property values for an entity type.
   *
   * Required properties differ from entity type to entity type, so we keep a
   * minimum mapping here.
   *
   * @param string $entity_type
   *   The type of the entity that should be created.
   *
   * @return array
   *   An array of values keyed by property name.
   */
  protected function entityValues($entity_type) {
    switch ($entity_type) {
      case 'entity_test':
        return array('name' => $this->randomName(), 'user_id' => 1);
      case 'node':
        return array('title' => $this->randomString());
      case 'user':
        return array('name' => $this->randomName());
      default:
        return array();
    }
  }

  /**
   * Enables the web service interface for a specific entity type.
   *
   * @param string|FALSE $resource_type
   *   The resource type that should get web API enabled or FALSE to disable all
   *   resource types.
   */
  protected function enableService($resource_type) {
    // Enable web API for this entity type.
    $config = config('rest');
    if ($resource_type) {
      $config->set('resources', array(
        $resource_type => $resource_type,
      ));
    }
    else {
      $config->set('resources', array());
    }
    $config->save();

    // Rebuild routing cache, so that the web API paths are available.
    drupal_container()->get('router.builder')->rebuild();
    // Reset the Simpletest permission cache, so that the new resource
    // permissions get picked up.
    drupal_static_reset('checkPermissions');
  }

  /**
   * Check if a HTTP response header exists and has the expected value.
   *
   * @param string $header
   *   The header key, example: Content-Type
   * @param string $value
   *   The header value.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertHeader($header, $value, $message = '', $group = 'Browser') {
    $match = isset($this->responseHeaders[$header]) && $this->responseHeaders[$header] == $value;
    return $this->assertTrue($match, $message ? $message : 'HTTP response header ' . $header . ' with value ' . $value . ' found.', $group);
  }
}
