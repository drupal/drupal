<?php

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Test
 * @group legacy
 *
 * @coversDefaultClass \Drupal\KernelTests\KernelTestBase
 */
class KernelTestBaseTest extends UnitTestCase {

  /**
   * @expectedDeprecation Drupal\KernelTests\KernelTestBase::isTestInIsolation() is deprecated in Drupal 8.4.x, for removal before the Drupal 9.0.0 release. KernelTestBase tests are always run in isolated processes.
   *
   * @covers ::isTestInIsolation
   */
  public function testDeprecatedIsTestInIsolation() {
    $kernel_test = $this->getMockBuilder(KernelTestBase::class)
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $is_isolated = new \ReflectionMethod($kernel_test, 'isTestInIsolation');
    $is_isolated->setAccessible(TRUE);

    // Assert that the return value is a bool, because this unit test might or
    // might not be running in process isolation.
    $this->assertInternalType('bool', $is_isolated->invoke($kernel_test));
  }

}
