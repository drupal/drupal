<?php

namespace Drupal\Tests;

use Drupal\Tests\Traits\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\Tests\Traits\ExpectDeprecationTrait
 *
 * @group Test
 * @group legacy
 *
 * Do not remove this test when \Drupal\Tests\Traits\ExpectDeprecationTrait is
 * removed. Change it to use \Symfony\Bridge\PhpUnit\ExpectDeprecationTrait
 * instead to ensure Drupal has test coverage of Symfony's deprecation testing.
 */
class ExpectDeprecationTest extends UnitTestCase {
  use ExpectDeprecationTrait;

  /**
   * @covers ::addExpectedDeprecationMessage
   */
  public function testExpectDeprecation() {
    $this->expectDeprecation('Drupal\Tests\Traits\ExpectDeprecationTrait::addExpectedDeprecationMessage() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Symfony\Bridge\PhpUnit\ExpectDeprecationTrait::expectDeprecation() instead. See https://www.drupal.org/node/3161901');
    $this->addExpectedDeprecationMessage('Test deprecation');
    @trigger_error('Test deprecation', E_USER_DEPRECATED);
  }

  /**
   * @covers ::addExpectedDeprecationMessage
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testExpectDeprecationInIsolation() {
    $this->expectDeprecation('Drupal\Tests\Traits\ExpectDeprecationTrait::addExpectedDeprecationMessage() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Symfony\Bridge\PhpUnit\ExpectDeprecationTrait::expectDeprecation() instead. See https://www.drupal.org/node/3161901');
    $this->expectDeprecation('Drupal\Tests\Traits\ExpectDeprecationTrait::expectedDeprecations() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Symfony\Bridge\PhpUnit\ExpectDeprecationTrait::expectDeprecation() instead. See https://www.drupal.org/node/3161901');
    $this->addExpectedDeprecationMessage('Test isolated deprecation');
    $this->expectedDeprecations(['Test isolated deprecation2']);
    @trigger_error('Test isolated deprecation', E_USER_DEPRECATED);
    @trigger_error('Test isolated deprecation2', E_USER_DEPRECATED);
  }

}
