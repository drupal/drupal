<?php

namespace Drupal\rest_test\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Class used to test that serialization_class is optional.
 *
 * @RestResource(
 *   id = "serialization_test",
 *   label = @Translation("Optional serialization_class"),
 *   serialization_class = "",
 *   uri_paths = {}
 * )
 */
class NoSerializationClassTestResource extends ResourceBase {

  /**
   * Responds to a POST request.
   *
   * @param array $data
   *   An array with the payload.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post(array $data = []) {
    return new ResourceResponse($data);
  }

}
