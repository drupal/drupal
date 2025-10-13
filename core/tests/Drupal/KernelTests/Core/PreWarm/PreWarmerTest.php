<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\PreWarm;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the cache prewarmer.
 */
#[Group('PreWarm')]
#[RunTestsInSeparateProcesses]
class PreWarmerTest extends KernelTestBase {

  /**
   * Tests prewarming all caches.
   */
  public function testPreWarmAllCaches(): void {
    $prewarmer = \Drupal::service('cache_prewarmer');
    $this->assertTrue($prewarmer->preWarmAllCaches());
    $this->assertFalse($prewarmer->preWarmAllCaches());
  }

  /**
   * Tests prewarming one cache at a time.
   */
  public function testPreWarmOneCache(): void {
    $prewarmer = \Drupal::service('cache_prewarmer');

    // Make sure at least one prewarmable service actually gets called.
    $called = FALSE;
    while ($prewarmer->preWarmOneCache()) {
      $called = TRUE;
    }
    $this->assertTrue($called);
    $this->assertFalse($prewarmer->preWarmOneCache());
  }

}
