<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\Entity\MigrationTest.
 */

namespace Drupal\Tests\migrate\Unit\Entity;

use Drupal\migrate\Plugin\Migration;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the migrate entity.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\Migration
 * @group migrate
 */
class MigrationTest extends UnitTestCase {

  /**
   * Tests Migration::getProcessPlugins()
   *
   * @covers ::getProcessPlugins
   */
  public function testGetProcessPlugins() {
    $migration = new Migration([], uniqid(), []);
    $this->assertEquals([], $migration->getProcessPlugins([]));
  }

}
