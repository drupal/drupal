<?php

namespace Drupal\FunctionalTests;

use Behat\Mink\Driver\GoutteDriver;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests legacy support for GoutteDriver.
 *
 * @group browsertestbase
 */
class GoutteDriverTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = GoutteDriver::class;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests basic page test.
   *
   * @group legacy
   */
  public function testGoTo() {
    $this->expectDeprecation('Using \Behat\Mink\Driver\GoutteDriver is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. The dependencies behat/mink-goutte-driver and fabpot/goutte will be removed. See https://www.drupal.org/node/3177235');
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Visit a Drupal page that requires login.
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    // Test page contains some text.
    $this->assertSession()->pageTextContains('Test page text.');

    // Check that returned plain text is correct.
    $text = $this->getTextContent();
    $this->assertStringContainsString('Test page text.', $text);
    $this->assertStringNotContainsString('</html>', $text);

    // Response includes cache tags that we can assert.
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Tags');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'http_response rendered');

    // Test that we can read the JS settings.
    $js_settings = $this->getDrupalSettings();
    $this->assertSame('azAZ09();.,\\\/-_{}', $js_settings['test-setting']);

    // Test drupalGet with a url object.
    $url = Url::fromRoute('test_page_test.render_title');
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test page contains some text.
    $this->assertSession()->pageTextContains('Hello Drupal');

    // Ensure that \Drupal\Tests\UiHelperTrait::isTestUsingGuzzleClient() works
    // as expected.
    $this->assertTrue($this->isTestUsingGuzzleClient());
  }

}
