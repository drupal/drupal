<?php

namespace Drupal\FunctionalJavascriptTests\Core;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test race condition for CSRF tokens for simultaneous requests.
 *
 * @group Session
 */
class CsrfTokenRaceTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['csrf_race_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test race condition for CSRF tokens for simultaneous requests.
   */
  public function testCsrfRace() {
    $user = $this->createUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('/csrf_race/test');
    $script = '';
    // Delay the request processing of the first request by one second through
    // the request parameter, which will simulate the concurrent processing
    // of both requests.
    foreach ([1, 0] as $i) {
      $script .= <<<EOT
      jQuery.ajax({
        url: "$this->baseUrl/csrf_race/get_csrf_token/$i",
        method: "GET",
        headers: {
          "Content-Type": "application/json"
        },
        success: function(response) {
          jQuery('body').append("<p class='csrf$i'></p>");
          jQuery('.csrf$i').html(response);
        },
        error: function() {
          jQuery('body').append('Nothing');
        }
      });
EOT;
    }
    $this->getSession()->getDriver()->executeScript($script);
    $token0 = $this->assertSession()->waitForElement('css', '.csrf0')->getHtml();
    $token1 = $this->assertSession()->waitForElement('css', '.csrf1')->getHtml();
    $this->assertNotNull($token0);
    $this->assertNotNull($token1);
    $this->assertEqual($token0, $token1);
  }

}
