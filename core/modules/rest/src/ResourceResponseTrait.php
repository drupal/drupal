<?php

namespace Drupal\rest;

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
