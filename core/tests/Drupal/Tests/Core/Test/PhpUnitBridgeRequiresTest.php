<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass;

/**
 * Test how unit tests interact with deprecation errors.
 *
 * If a test requires an extension that does not exist and has a data provider
 * the interaction between Drupal and Symfony's deprecation testing can cause
 * errors. This test proves this is not broken.
 *
 * This test will be skipped and should not cause the test suite to fail.
 *
 * @group Test
 * @requires extension will_hopefully_never_exist
 * @see \Drupal\Tests\Listeners\DrupalListener
 */
class PhpUnitBridgeRequiresTest extends UnitTestCase {

  /**
   * Tests the @requires annotation.
   *
   * @dataProvider providerTestWillNeverRun
   */
  public function testWillNeverRun(): void {
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  /**
   * Data provider for ::testWillNeverRun().
   */
  public function providerTestWillNeverRun(): array {
    return [
      ['this_will_never_run'],
    ];
  }

}
