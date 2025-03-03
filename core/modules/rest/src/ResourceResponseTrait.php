<?php

namespace Drupal\rest;

/**
 * Provides a trait for accessing response data that should be serialized.
 */
trait ResourceResponseTrait {

  /**
   * Response data that should be serialized.
   *
   * @var mixed
   */
  protected $responseData;

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
