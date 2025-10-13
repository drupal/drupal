<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Asset;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Asset\AssetQueryString;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the asset query string functionality.
 */
#[CoversClass(AssetQueryString::class)]
#[Group('Asset')]
#[RunTestsInSeparateProcesses]
class AssetQueryStringTest extends KernelTestBase {

  /**
   * Tests reset get.
   *
   * @legacy-covers ::get
   * @legacy-covers ::reset
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
