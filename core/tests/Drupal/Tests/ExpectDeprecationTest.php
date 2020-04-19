<?php

namespace Drupal\Tests;

use Drupal\Tests\Traits\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\Tests\Traits\ExpectDeprecationTrait
 *
 * @group Test
 * @group legacy
 */
class ExpectDeprecationTest extends UnitTestCase {
  use ExpectDeprecationTrait;

  /**
   * @covers ::addExpectedDeprecationMessage
   */
  public function testExpectDeprecation() {
    $this->addExpectedDeprecationMessage('Test deprecation');
    @trigger_error('Test deprecation', E_USER_DEPRECATED);
  }

  /**
   * @covers ::addExpectedDeprecationMessage
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testExpectDeprecationInIsolation() {
    $this->addExpectedDeprecationMessage('Test isolated deprecation');
    $this->addExpectedDeprecationMessage('Test isolated deprecation2');
    @trigger_error('Test isolated deprecation', E_USER_DEPRECATED);
    @trigger_error('Test isolated deprecation2', E_USER_DEPRECATED);
  }

  /**
   * @covers ::expectDeprecation
   *
   * @todo the expectedDeprecation annotation does not work if tests are marked
   *   skipped.
   * @see https://github.com/symfony/symfony/pull/25757
   */
  public function testDeprecatedExpectDeprecation() {
    $this->addExpectedDeprecationMessage('ExpectDeprecationTrait::expectDeprecation is deprecated in drupal:8.8.5 and is removed from drupal:9.0.0. Use ::addExpectedDeprecationMessage() instead. See https://www.drupal.org/node/3106024');
    $this->expectDeprecation('Test deprecated expectDeprecation');
    @trigger_error('Test deprecated expectDeprecation', E_USER_DEPRECATED);
  }

}
