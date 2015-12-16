<?php

namespace Zumba\Mink\Driver;

/**
 * Trait NavigationTrait
 * @package Zumba\Mink\Driver
 */
trait NavigationTrait {
  /**
   * Visits a given url
   * @param string $url
   */
  public function visit($url) {
    $this->browser->visit($url);
  }

  /**
   * Gets the current url if any
   * @return string
   */
  public function getCurrentUrl() {
    return $this->browser->currentUrl();
  }


  /**
   * Reloads the page if possible
   */
  public function reload() {
    $this->browser->reload();
  }

  /**
   * Goes forward if possible
   */
  public function forward() {
    $this->browser->goForward();
  }

  /**
   * Goes back if possible
   */
  public function back() {
    $this->browser->goBack();
  }


}
