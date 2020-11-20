<?php

namespace Zumba\GastonJS\Exception;

/**
 * Class TimeoutError
 * @package Zumba\GastonJS\Exception
 */
class TimeoutError extends \Exception {

  /**
   * @param string $message
   */
  public function __construct($message) {
    $errorMessage = "Timed out waiting for response to {$message}. It's possible that this happened
            because something took a very long time(for example a page load was slow).
            If so, setting the Poltergeist :timeout option to a higher value will help
            (see the docs for details). If increasing the timeout does not help, this is
            probably a bug in Poltergeist - please report it to the issue tracker.";
    parent::__construct($errorMessage);
  }

}
