<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Exception\DriverException;

/**
 * Class JavascriptTrait
 * @package Zumba\Mink\Driver
 */
trait JavascriptTrait {

  /**
   * Executes a script on the browser
   * @param string $script
   */
  public function executeScript($script) {
    $this->browser->execute($script);
  }

  /**
   * Evaluates a script and returns the result
   * @param string $script
   * @return mixed
   */
  public function evaluateScript($script) {
    return $this->browser->evaluate($script);
  }

  /**
   * Waits some time or until JS condition turns true.
   *
   * @param integer $timeout timeout in milliseconds
   * @param string  $condition JS condition
   * @return boolean
   * @throws DriverException                  When the operation cannot be done
   */
  public function wait($timeout, $condition) {
    $start = microtime(true);
    $end = $start + $timeout / 1000.0;
    do {
      $result = $this->browser->evaluate($condition);
      usleep(100000);
    } while (microtime(true) < $end && !$result);

    return (bool)$result;
  }

}
