<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Database\Connection;
use Drupal\layout_builder\InlineBlockUsage;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\InlineBlockUsage
 *
 * @group layout_builder
 */
class InlineBlockUsageTest extends UnitTestCase {

  /**
   * Tests calling deleteUsage() with empty array.
   *
   * @covers ::deleteUsage
   */
  public function testEmptyDeleteUsageCall() {
    $connection = $this->prophesize(Connection::class);
    $connection->delete('inline_block_usage')->shouldNotBeCalled();

    (new InlineBlockUsage($connection->reveal()))->deleteUsage([]);
  }

}
