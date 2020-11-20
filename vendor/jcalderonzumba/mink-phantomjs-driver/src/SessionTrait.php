<?php


namespace Zumba\Mink\Driver;

/**
 * Trait SessionTrait
 * @package Zumba\Mink\Driver
 */
trait SessionTrait {

  /** @var  bool */
  protected $started;

  /**
   * Starts a session to be used by the driver client
   */
  public function start() {
    $this->started = true;
  }

  /**
   * Tells if the session is started or not
   * @return bool
   */
  public function isStarted() {
    return $this->started;
  }

  /**
   * Stops the session completely, clean slate for the browser
   * @return bool
   */
  public function stop() {
    //Since we are using a remote browser "API", stopping is just like resetting, say good bye to cookies
    //TODO: In the future we may want to control a start / stop of the remove browser
    return $this->reset();
  }

  /**
   * Clears the cookies in the browser, all of them
   * @return bool
   */
  public function reset() {
    $this->getBrowser()->clearCookies();
    $this->getBrowser()->reset();
    $this->started = false;
    return true;
  }
}
