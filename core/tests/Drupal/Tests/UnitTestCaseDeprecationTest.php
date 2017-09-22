<?php

namespace Drupal\Tests;

/**
 * @coversDefaultClass \Drupal\Tests\UnitTestCase
 *
 * @group Test
 * @group legacy
 */
class UnitTestCaseDeprecationTest extends UnitTestCase {

  /**
   * @covers ::getBlockMockWithMachineName
   * @expectedDeprecation Drupal\Tests\UnitTestCase::getBlockMockWithMachineName is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Unit test base classes should not have dependencies on extensions. Set up mocks in individual tests.
   */
  public function testDeprecatedGetBlockMockWithMachineName() {
    $block_mock = $this->getBlockMockWithMachineName('test_name');
    $this->assertEquals('test_name', $block_mock->getPlugin()->getMachineNameSuggestion());
  }

}
