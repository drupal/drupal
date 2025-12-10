<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Drupal\FunctionalJavascriptTests\WebDriverTestBase.
 */
#[CoversClass(WebDriverTestBase::class)]
#[Group('Test')]
#[RunTestsInSeparateProcesses]
class WebDriverTestBaseTest extends UnitTestCase {

  /**
   * Tests W3C setting is added to goog:chromeOptions as expected.
   *
   * @legacy-covers ::getMinkDriverArgs
   */
  #[TestWith([FALSE, NULL])]
  #[TestWith([FALSE, ""])]
  #[TestWith(["", "", ""])]
  #[TestWith([
    "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":true,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]",
    "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":true,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]",
  ])]
  #[TestWith([
    "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":false,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]",
    "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":false,\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]",
  ])]
  #[TestWith([
    "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"args\":[\"--headless\"],\"w3c\":false}},\"http:\\/\\/localhost:4444\"]",
    "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"args\":[\"--headless\"]}},\"http:\\/\\/localhost:4444\"]",
  ])]
  #[TestWith([
    "[\"chrome\",{\"browserName\":\"chrome\",\"goog:chromeOptions\":{\"w3c\":false}},\"http:\\/\\/localhost:4444\"]",
    "[\"chrome\",{\"browserName\":\"chrome\"},\"http:\\/\\/localhost:4444\"]",
  ])]
  public function testCapabilities($expected, ?string $mink_driver_args_webdriver, ?string $mink_driver_args = NULL): void {
    $this->putEnv("MINK_DRIVER_ARGS_WEBDRIVER", $mink_driver_args_webdriver);
    $this->putEnv("MINK_DRIVER_ARGS", $mink_driver_args);

    // @phpstan-ignore testClass.missingAttribute.Group, testClass.missingAttribute.RunInSeparateProcesses
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
   *   No return value.
   */
  private function putEnv(string $variable, ?string $value): void {
    if (is_string($value)) {
      putenv($variable . "=" . $value);
    }
    else {
      putenv($variable);
    }
  }

}
