<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Asset;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Asset\AssetQueryString;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the asset query string functionality.
 *
 * @group Asset
 * @coversDefaultClass \Drupal\Core\Asset\AssetQueryString
 */
class AssetQueryStringTest extends KernelTestBase {

  /**
   * @covers ::get
   * @covers ::reset
   */
  public function testResetGet(): void {
    $state = $this->container->get('state');
    // Return a fixed timestamp.
    $time = $this->createStub(TimeInterface::class);
    $time->method('getRequestTime')
      ->willReturn(1683246590);

    $queryString = new AssetQueryString($state, $time);

    $queryString->reset();
    $value = $queryString->get();

    $this->assertEquals('ru5tdq', $value);
  }

}
