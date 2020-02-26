<?php

namespace Drupal\FunctionalTests\Core\Test;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Drupal's integration with Symfony PHPUnit Bridge.
 *
 * @group Test
 * @group legacy
 */
class PhpUnitBridgeTest extends BrowserTestBase {

  protected static $modules = ['deprecation_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @expectedDeprecation This is the deprecation message for deprecation_test_function().
   */
  public function testSilencedError() {
    $this->assertEquals('known_return_value', deprecation_test_function());
  }

  /**
   * @expectedDeprecation This is the deprecation message for deprecation_test_function().
   */
  public function testErrorOnSiteUnderTest() {
    $this->drupalGet(Url::fromRoute('deprecation_test.route'));
  }

}
