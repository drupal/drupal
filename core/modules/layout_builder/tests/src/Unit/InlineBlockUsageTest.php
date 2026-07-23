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
    $connection = $this->createMock(Connection::class);
    $connection->expects($this->never())->method('delete');

    (new InlineBlockUsage($connection))->deleteUsage([]);
  }

}
