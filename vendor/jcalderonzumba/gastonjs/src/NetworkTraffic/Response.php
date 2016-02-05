<?php
namespace Zumba\GastonJS\NetworkTraffic;

/**
 * Class Response
 * @package Zumba\GastonJS\NetworkTraffic
 */
class Response {
  /** @var  array */
  protected $data;

  /**
   * @param $data
   */
  public function __construct($data) {
    $this->data = $data;
  }

  /**
   * Gets Response url
   * @return string
   */
  public function getUrl() {
    return $this->data['url'];
  }

  /**
   * Gets the response status code
   * @return int
   */
  public function getStatus() {
    return intval($this->data['status']);
  }

  /**
   * Gets the status text of the response
   * @return string
   */
  public function getStatusText() {
    return $this->data['statusText'];
  }

  /**
   * Gets the response headers
   * @return array
   */
  public function getHeaders() {
    return $this->data['headers'];
  }

  /**
   * Get redirect url if response is a redirect
   * @return string
   */
  public function getRedirectUrl() {
    if (isset($this->data['redirectUrl']) && !empty($this->data['redirectUrl'])) {
      return $this->data['redirectUrl'];
    }
    return null;
  }

  /**
   * Returns the size of the response body
   * @return int
   */
  public function getBodySize() {
    if (isset($this->data['bodySize'])) {
      return intval($this->data['bodySize']);
    }
    return 0;
  }

  /**
   * Returns the content type of the response
   * @return string
   */
  public function getContentType() {
    if (isset($this->data['contentType'])) {
      return $this->data['contentType'];
    }
    return null;
  }

  /**
   * Returns if exists the response time
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
