<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\FunctionalJavascriptTests\WebDriverTestBase
 * @group Test
 * @runTestsInSeparateProcesses
 */
class WebDriverTestBaseTest extends UnitTestCase {

  /**
   * Tests W3C setting is added to goog:chromeOptions as expected.
   *
   * @testWith [false, null]
   *           [false, ""]
   *           ["", "", ""]
   *           ["[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":true,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]", "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":true,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]"]
   *           ["[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":false,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]", "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":false,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]"]
   *           ["[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"args\":[\"--headless\"],\"w3c\":false}},\"http:\\/\\/localhost:4444\"]", "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]"]
   *           ["[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":false}},\"http:\\/\\/localhost:4444\"]", "[\"chrome\",{\"browserName\":\"chrome\"},\"http:\\/\\/localhost:4444\"]"]
   *
   * @covers ::getMinkDriverArgs
   */
  public function testCapabilities($expected, ?string $mink_driver_args_webdriver, ?string $mink_driver_args = NULL): void {
    $this->putEnv("MINK_DRIVER_ARGS_WEBDRIVER", $mink_driver_args_webdriver);
    $this->putEnv("MINK_DRIVER_ARGS", $mink_driver_args);

    $object = new class('test') extends WebDriverTestBase {
    };
    $method = new \ReflectionMethod($object, 'getMinkDriverArgs');
    $this->assertSame($expected, $method->invoke($object));
  }

  /**
   * Sets or deletes an environment variable.
   *
   * @param string $variable
   *   The environment variable to set or delete.
   * @param string|null $value
   *   The value to set the variable to. If the value is NULL then the
   *   environment variable will be unset.
   *
   * @return void
   */
  private function putEnv(string $variable, ?string $value): void {
    if (is_string($value)) {
      putenv($variable . "=" . $value);
    }
    else {
      putenv($variable);
    }
  }

  /**
   * Tests "chromeOptions" deprecation.
   *
   * @group legacy
   *
   * @covers ::getMinkDriverArgs
   */
  public function testChromeOptions(): void {
    $this->expectDeprecation('The "chromeOptions" array key is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use "goog:chromeOptions instead. See https://www.drupal.org/node/3422624');
    putenv('MINK_DRIVER_ARGS_WEBDRIVER=["chrome",{"browserName":"chrome","chromeOptions":{"args":["--headless"]}},"http://localhost:4444"]');

    $object = new class('test') extends WebDriverTestBase {
    };
    $method = new \ReflectionMethod($object, 'getMinkDriverArgs');
    $this->assertSame('["chrome",{"browserName":"chrome","goog:chromeOptions":{"args":["--headless"],"w3c":false}},"http:\\/\\/localhost:4444"]', $method->invoke($object));
  }

}
