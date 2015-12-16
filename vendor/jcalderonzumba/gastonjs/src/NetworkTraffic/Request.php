<?php

namespace Zumba\GastonJS\NetworkTraffic;

/**
 * Class Request
 * @package Zumba\GastonJS\NetworkTraffic
 */
class Request {
  /** @var array */
  protected $data;
  /** @var array */
  protected $responseParts;


  /**
   * @param array $data
   * @param array $responseParts
   */
  public function __construct($data, $responseParts = null) {
    $this->data = $data;
    $this->responseParts = $this->createResponseParts($responseParts);
  }

  /**
   * Creates an array of Response objects from a given response array
   * @param $responseParts
   * @return array
   */
  protected function createResponseParts($responseParts) {
    if ($responseParts === null) {
      return array();
    }
    $responses = array();
    foreach ($responseParts as $responsePart) {
      $responses[] = new Response($responsePart);
    }
    return $responses;
  }

  /**
   * @return array
   */
  public function getResponseParts() {
    return $this->responseParts;
  }

  /**
   * @param array $responseParts
   */
  public function setResponseParts($responseParts) {
    $this->responseParts = $responseParts;
  }

  /**
   * Returns the url where the request is going to be made
   * @return string
   */
  public function getUrl() {
    //TODO: add isset maybe?
    return $this->data['url'];
  }

  /**
   * Returns the request method
   * @return string
   */
  public function getMethod() {
    return $this->data['method'];
  }

  /**
   * Gets the request headers
   * @return array
   */
  public function getHeaders() {
    //TODO: Check if the data is actually an array, else make it array and see implications
    return $this->data['headers'];
  }

  /**
   * Returns if exists the request time
   * @return \DateTime
   */
  public function getTime() {
    if (isset($this->data['time'])) {
      $requestTime = new \DateTime();
      //TODO: fix the microseconds to miliseconds
      $requestTime->createFromFormat("Y-m-dTH:i:s.uZ", $this->data["time"]);
      return $requestTime;
    }
    return null;
  }

}
