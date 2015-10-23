<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserHeadersTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserHeadersTrait {
  /**
   * Returns the headers of the current page that will be used the next request
   * @return mixed
   */
  public function getHeaders() {
    return $this->command('get_headers');
  }

  /**
   * Given an array of headers, set such headers for the requests, removing all others
   * @param array $headers
   * @return mixed
   */
  public function setHeaders($headers) {
    return $this->command('set_headers', $headers);
  }

  /**
   * Adds headers to current page overriding the existing ones for the next requests
   * @param $headers
   * @return mixed
   */
  public function addHeaders($headers) {
    return $this->command('add_headers', $headers);
  }

  /**
   * Adds a header to the page making it permanent if needed
   * @param $header
   * @param $permanent
   * @return mixed
   */
  public function addHeader($header, $permanent = false) {
    return $this->command('add_header', $header, $permanent);
  }

  /**
   * Gets the response headers after a request
   * @return mixed
   */
  public function responseHeaders() {
    return $this->command('response_headers');
  }
}
