<?php

namespace Zumba\GastonJS\Exception;


/**
 * Class StatusFailError
 * @package Zumba\GastonJS\Exception
 */
class StatusFailError extends ClientError {
  /**
   * @return string
   */
  public function message() {
    return "Request failed to reach server, check DNS and/or server status";
  }
}
