<?php

namespace Zumba\Mink\Driver;

use Zumba\GastonJS\Cookie;

/**
 * Trait CookieTrait
 * @package Zumba\Mink\Driver
 */
trait CookieTrait {

  /**
   * Sets a cookie on the browser, if null value then delete it
   * @param string $name
   * @param string $value
   */
  public function setCookie($name, $value = null) {
    if ($value === null) {
      $this->browser->removeCookie($name);
    }
    //TODO: set the cookie with domain, not with url, meaning www.aaa.com or .aaa.com
    if ($value !== null) {
      $urlData = parse_url($this->getCurrentUrl());
      $cookie = array("name" => $name, "value" => $value, "domain" => $urlData["host"]);
      $this->browser->setCookie($cookie);
    }
  }

  /**
   * Gets a cookie by its name if exists, else it will return null
   * @param string $name
   * @return string
   */
  public function getCookie($name) {
    $cookies = $this->browser->cookies();
    foreach ($cookies as $cookie) {
      if ($cookie instanceof Cookie && strcmp($cookie->getName(), $name) === 0) {
        return $cookie->getValue();
      }
    }
    return null;
  }

}
