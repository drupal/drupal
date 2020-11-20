<?php

namespace Zumba\GastonJS\Browser;

use Zumba\GastonJS\Cookie;

/**
 * Trait BrowserCookieTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserCookieTrait {
  /**
   * Gets the cookies on the browser
   *
   * @return Cookie[]
   */
  public function cookies() {
    $cookies = $this->command('cookies');
    $objCookies = array();
    foreach ($cookies as $cookie) {
      $objCookies[$cookie["name"]] = new Cookie($cookie);
    }
    return $objCookies;
  }

  /**
   * Sets a cookie on the browser, expires times is set in seconds
   * @param $cookie
   * @return mixed
   */
  public function setCookie($cookie) {
    //TODO: add error control when the cookie array is not valid
    if (isset($cookie["expires"])) {
      $cookie["expires"] = intval($cookie["expires"]) * 1000;
    }
    $cookie['value'] = urlencode($cookie['value']);
    return $this->command('set_cookie', $cookie);
  }

  /**
   * Deletes a cookie on the browser if exists
   * @param $cookieName
   * @return bool
   */
  public function removeCookie($cookieName) {
    return $this->command('remove_cookie', $cookieName);
  }

  /**
   * Clear all the cookies
   * @return bool
   */
  public function clearCookies() {
    return $this->command('clear_cookies');
  }

  /**
   * Enables or disables the cookies con phantomjs
   * @param bool $enabled
   * @return bool
   */
  public function cookiesEnabled($enabled = true) {
    return $this->command('cookies_enabled', $enabled);
  }
}
