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
        return $this->curlExec(array(
          CURLOPT_HTTPGET => TRUE,
          CURLOPT_URL => url($url, $options),
          CURLOPT_NOBODY => FALSE)
        );
      case 'POST':
        return $this->curlExec(array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('Content-Type: ' . $format),
        ));
      case 'PUT':
        return $this->curlExec(array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('Content-Type: ' . $format),
        ));
      case 'DELETE':
        return $this->curlExec(array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
        ));
    }
  }
}
