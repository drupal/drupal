<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserPageTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserPageTrait {
  /**
   * Gets the status code of the request we are currently in
   * @return mixed
   */
  public function getStatusCode() {
    return $this->command('status_code');
  }

  /**
   * Returns the body of the response to a given browser request
   * @return mixed
   */
  public function getBody() {
    return $this->command('body');
  }

  /**
   * Returns the source of the current page
   * @return mixed
   */
  public function getSource() {
    return $this->command('source');
  }

  /**
   * Gets the current page title
   * @return mixed
   */
  public function getTitle() {
    return $this->command('title');
  }

  /**
   * Resize the current page
   * @param $width
   * @param $height
   * @return mixed
   */
  public function resize($width, $height) {
    return $this->command('resize', $width, $height);
  }

  /**
   * Resets the page we are in to a clean slate
   * @return mixed
   */
  public function reset() {
    return $this->command('reset');
  }
}
