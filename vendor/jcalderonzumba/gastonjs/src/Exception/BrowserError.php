<?php

namespace Zumba\GastonJS\Exception;


/**
 * Class BrowserError
 * @package Zumba\GastonJS\Exception
 */
class BrowserError extends ClientError {

  /**
   * @param array $response
   */
  public function __construct($response) {
    parent::__construct($response);
    $this->message = $this->message();
  }

  /**
   * Gets the name of the browser error
   * @return string
   */
  public function getName() {
    return $this->response["error"]["name"];
  }

  /**
   * @return JSErrorItem
   */
  public function javascriptError() {
    //TODO: this need to be check, i don't know yet what comes in response
    return new JSErrorItem($this->response["error"]["args"][0], $this->response["error"]["args"][1]);
  }

  /**
   * Returns error message
   * TODO: check how to proper implement if we have exceptions
   * @return string
   */
  public function message() {
    return "There was an error inside the PhantomJS portion of GastonJS.\nThis is probably a bug, so please report it:\n" . $this->javascriptError();
  }
}
