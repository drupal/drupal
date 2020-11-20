<?php

namespace Zumba\GastonJS\Exception;

/**
 * Class ObsoleteNode
 * @package Zumba\GastonJS\Exception
 */
class ObsoleteNode extends ClientError {

  /**
   * @param array $response
   */
  public function __construct($response) {
    parent::__construct($response);
    $this->message = $this->message();
  }

  /**
   * @return string
   */
  public function message() {
    return "The element you are trying to interact with is either not part of the DOM, or is
    not currently visible on the page (perhaps display: none is set).
    It's possible the element has been replaced by another element and you meant to interact with
    the new element. If so you need to do a new 'find' in order to get a reference to the
    new element.";
  }
}
