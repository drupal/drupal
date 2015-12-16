<?php

namespace Zumba\GastonJS\Exception;


/**
 * Class JavascriptError
 * @package Zumba\GastonJS\Exception
 */
class JavascriptError extends ClientError {

  /**
   * @param array $response
   */
  public function __construct($response) {
    parent::__construct($response);
    $this->message = $this->message();
  }

  /**
   * Get the javascript errors found during the use of the phantomjs
   * @return array
   */
  public function javascriptErrors() {
    $jsErrors = array();
    $errors = $this->response["error"]["args"][0];
    foreach ($errors as $error) {
      $jsErrors[] = new JSErrorItem($error["message"], $error["stack"]);
    }
    return $jsErrors;
  }

  /**
   * Returns the javascript errors found
   * @return string
   */
  public function message() {
    $error = "One or more errors were raised in the Javascript code on the page.
            If you don't care about these errors, you can ignore them by
            setting js_errors: false in your Poltergeist configuration (see documentation for details).";
    //TODO: add javascript errors
    $jsErrors = $this->javascriptErrors();
    foreach($jsErrors as $jsError){
      $error = "$error\n$jsError";
    }
    return $error;
  }
}
