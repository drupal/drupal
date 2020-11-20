<?php

namespace Zumba\GastonJS\Exception;

/**
 * Class FrameNotFound
 * @package Zumba\GastonJS\Exception
 */
class FrameNotFound extends ClientError {

  /**
   * @return string
   */
  public function getName() {
    //TODO: check stuff here
    return current(reset($this->response["args"]));
  }

  /**
   * @return string
   */
  public function message() {
    //TODO: check the exception message stuff
    return "The frame " . $this->getName() . " was not not found";
  }
}
