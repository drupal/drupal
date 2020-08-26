<?php

namespace Drupal\jsonapi;

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
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\rest\ModifiedResourceResponse
 */
class ResourceResponse extends Response implements CacheableResponseInterface {

  use CacheableResponseTrait;

  /**
   * Response data that should be serialized.
   *
   * @var mixed
   */
  protected $responseData;

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
  public function __construct($data = NULL, $status = 200, array $headers = []) {
    $this->responseData = $data;
    parent::__construct('', $status, $headers);
  }

  /**
   * Returns response data that should be serialized.
   *
   * @return mixed
   *   Response data that should be serialized.
   */
  public function getResponseData() {
    return $this->responseData;
  }

}
