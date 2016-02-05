<?php

namespace Zumba\GastonJS\Exception;

/**
 * Class ClientError
 * @package Zumba\GastonJS\Exception
 */
class ClientError extends \Exception {

  /** @var mixed */
  protected $response;

  /**
   * @param mixed $response
   */
  public function __construct($response) {
    $this->response = $response;
  }

  /**
   * @return mixed
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * @param mixed $response
   */
  public function setResponse($response) {
    $this->response = $response;
  }


}
