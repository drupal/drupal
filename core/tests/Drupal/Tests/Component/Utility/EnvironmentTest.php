<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\EnvironmentTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Environment;
use Drupal\Tests\UnitTestCase;

/**
 * Test PHP Environment helper methods.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Environment
 */
class EnvironmentTest extends UnitTestCase {

  /**
   * Tests \Drupal\Component\Utility\Environment::checkMemoryLimit().
   *
   * @dataProvider providerTestCheckMemoryLimit
   * @covers ::checkMemoryLimit
   *
   * @param string $required
   *   The required memory argument for
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit().
   * @param string $custom_memory_limit
   *   The custom memory limit argument for
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit().
   * @param bool $expected
   *   The expected return value from
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit().
   */
  public function testCheckMemoryLimit($required, $custom_memory_limit, $expected) {
    $actual = Environment::checkMemoryLimit($required, $custom_memory_limit);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provides data for testCheckMemoryLimit().
   *
   * @return array
   *   An array of arrays, each containing the arguments for
   *   \Drupal\Component\Utility\Environment::checkMemoryLimit():
   *   required and memory_limit, and the expected return value.
   */
  public function providerTestCheckMemoryLimit() {
    $memory_limit = ini_get('memory_limit');
    $twice_avail_memory = ($memory_limit * 2) . 'MB';

    return array(
      // Minimal amount of memory should be available.
      array('30MB', NULL, TRUE),
      // Exceed a custom (unlimited) memory limit.
      array($twice_avail_memory, -1, TRUE),
      // Exceed a custom memory limit.
      array('30MB', '16MB', FALSE),
      // Available = required.
      array('30MB', '30MB', TRUE),
    );
  }

}
