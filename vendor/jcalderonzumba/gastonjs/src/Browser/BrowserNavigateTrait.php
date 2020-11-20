<?php

namespace Zumba\GastonJS\Browser;

use Zumba\GastonJS\Exception\BrowserError;

/**
 * Trait BrowserNavigateTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserNavigateTrait {

  /**
   * Send a visit command to the browser
   * @param $url
   * @return mixed
   */
  public function visit($url) {
    return $this->command('visit', $url);
  }

  /**
   * Gets the current url we are in
   * @return mixed
   */
  public function currentUrl() {
    return $this->command('current_url');
  }

  /**
   * Goes back on the browser history if possible
   * @return bool
   * @throws BrowserError
   * @throws \Exception
   */
  public function goBack() {
    return $this->command('go_back');
  }

  /**
   * Goes forward on the browser history if possible
   * @return mixed
   * @throws BrowserError
   * @throws \Exception
   */
  public function goForward() {
    return $this->command('go_forward');
  }

  /**
   * Reloads the current page we are in
   */
  public function reload() {
    return $this->command('reload');
  }
}
