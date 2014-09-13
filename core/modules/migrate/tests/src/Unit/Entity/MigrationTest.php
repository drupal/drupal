<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\Entity\MigrationTest.
 */

namespace Drupal\Tests\migrate\Unit\Entity;

use Drupal\migrate\Entity\Migration;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the migrate entity.
 *
 * @coversDefaultClass \Drupal\migrate\Entity\Migration
 */
class MigrationTest extends UnitTestCase {

  /**
   * Tests Migration::getProcessPlugins()
   *
   * @covers ::getProcessPlugins()
   */
  public function testGetProcessPlugins() {
    $migration = new Migration([], 'migration');
    $this->assertEquals([], $migration->getProcessPlugins([]));
  }

}
