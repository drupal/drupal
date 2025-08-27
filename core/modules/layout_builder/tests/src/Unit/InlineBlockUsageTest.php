<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Database\Connection;
use Drupal\layout_builder\InlineBlockUsage;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\layout_builder\InlineBlockUsage.
 */
#[CoversClass(InlineBlockUsage::class)]
#[Group('layout_builder')]
class InlineBlockUsageTest extends UnitTestCase {

  /**
   * Tests calling deleteUsage() with empty array.
   *
   * @legacy-covers ::deleteUsage
   */
  public function testEmptyDeleteUsageCall(): void {
    $connection = $this->prophesize(Connection::class);
    $connection->delete('inline_block_usage')->shouldNotBeCalled();

    (new InlineBlockUsage($connection->reveal()))->deleteUsage([]);
  }

}
