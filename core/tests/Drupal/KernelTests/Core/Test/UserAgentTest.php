<?php

namespace Drupal\KernelTests\Core\Test;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests \Drupal\Tests\UserAgent.
 *
 * @group Test
 * @group FunctionalTests
 *
 * @coversDefaultClass \Drupal\Core\Test\UserAgent
 */
class UserAgentTest extends KernelTestBase {

  /**
   * Test that drupal_valid_test_ua() return expected string.
   *
   * @covers \drupal_valid_test_ua()
   */
  public function testDrupalValidTestUa() {
    $this->assertStringContainsString('test', drupal_valid_test_ua());
  }

}
