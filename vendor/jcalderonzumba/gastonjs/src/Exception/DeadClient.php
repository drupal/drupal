<?php

namespace Zumba\GastonJS\Exception;


/**
 * Class DeadClient
 * @package Zumba\GastonJS\Exception
 */
class DeadClient extends \Exception {

  /**
   * @param string     $message
   * @param int        $code
   * @param \Exception $previous
   */
  public function __construct($message = "", $code = 0, \Exception $previous = null) {
    $errorMsg = $message."\nPhantomjs browser server is not taking connections, most probably it has crashed\n";
    parent::__construct($errorMsg, $code, $previous);
  }
}
