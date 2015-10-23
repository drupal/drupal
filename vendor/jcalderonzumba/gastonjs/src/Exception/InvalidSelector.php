<?php

namespace Zumba\GastonJS\Exception;

/**
 * Class InvalidSelector
 * @package Zumba\GastonJS\Exception
 */
class InvalidSelector extends ClientError {
  /**
   * Gets the method of selection
   * @return string
   */
  public function getMethod() {
    return $this->response["error"]["args"][0];
  }

  /**
   * Gets the selector related to the method
   * @return string
   */
  public function getSelector() {
    return $this->response["error"]["args"][1];
  }

  /**
   * @return string
   */
  public function message() {
    return "The browser raised a syntax error while trying to evaluate" . $this->getMethod() . " selector " . $this->getSelector();
  }
}
