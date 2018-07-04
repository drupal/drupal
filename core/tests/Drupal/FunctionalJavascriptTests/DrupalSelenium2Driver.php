<?php

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Driver\Selenium2Driver;

/**
 * Provides a driver for Selenium testing.
 */
class DrupalSelenium2Driver extends Selenium2Driver {

  /**
   * {@inheritdoc}
   */
  public function setCookie($name, $value = NULL) {
    if ($value === NULL) {
      $this->getWebDriverSession()->deleteCookie($name);
      return;
    }

    $cookieArray = [
      'name' => $name,
      'value' => urlencode($value),
      'secure' => FALSE,
      // Unlike \Behat\Mink\Driver\Selenium2Driver::setCookie we set a domain
      // and an expire date, as otherwise cookies leak from one test site into
      // another.
      'domain' => parse_url($this->getWebDriverSession()->url(), PHP_URL_HOST),
      'expires' => time() + 80000,
    ];

    $this->getWebDriverSession()->setCookie($cookieArray);
  }

}
