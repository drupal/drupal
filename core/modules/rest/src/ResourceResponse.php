<?php

namespace Drupal\rest;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains data for serialization before sending the response.
 *
 * We do not want to abuse the $content property on the Response class to store
 * our response data. $content implies that the provided data must either be a
 * string or an object with a __toString() method, which is not a requirement
 * for data used here.
 *
 * @see \Drupal\rest\ModifiedResourceResponse
 */
class ResourceResponse extends Response implements CacheableResponseInterface, ResourceResponseInterface {

  use CacheableResponseTrait;
  use ResourceResponseTrait;

  /**
   * Constructor for ResourceResponse objects.
   *
   * @param mixed $data
   *   Response data that should be serialized.
   * @param int $status
   *   The response status code.
   * @param array $headers
   *   An array of response headers.
   */
  public function __construct($data = NULL, $status = 200, $headers = array()) {
    $this->responseData = $data;
    parent::__construct('', $status, $headers);
  }

}
