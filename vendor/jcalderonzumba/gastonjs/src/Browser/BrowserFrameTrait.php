<?php

namespace Zumba\GastonJS\Browser;

/**
 * Trait BrowserFrameTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserFrameTrait {
  /**
   * Back to the parent of the iframe if possible
   * @return mixed
   * @throws \Zumba\GastonJS\Exception\BrowserError
   * @throws \Exception
   */
  public function popFrame() {
    return $this->command("pop_frame");
  }

  /**
   * Goes into the iframe to do stuff
   * @param string $name
   * @param int    $timeout
   * @return mixed
   * @throws \Zumba\GastonJS\Exception\BrowserError
   * @throws \Exception
   */
  public function pushFrame($name, $timeout = null) {
    return $this->command("push_frame", $name, $timeout);
  }
}
