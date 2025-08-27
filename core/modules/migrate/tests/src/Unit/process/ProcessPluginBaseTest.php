<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\ProcessPluginBase as CoreProcessPluginBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the base process plugin class.
 */
#[CoversClass(CoreProcessPluginBase::class)]
#[Group('migrate')]
class ProcessPluginBaseTest extends UnitTestCase {

  /**
   * Tests stopping the pipeline.
   *
   * @legacy-covers ::isPipelineStopped
   * @legacy-covers ::stopPipeline
   * @legacy-covers ::reset
   */
  public function testStopPipeline(): void {
    $plugin = new ProcessPluginBase([], 'plugin_id', []);
    $this->assertFalse($plugin->isPipelineStopped());
    $stopPipeline = (new \ReflectionClass($plugin))->getMethod('stopPipeline');
    $stopPipeline->invoke($plugin);
    $this->assertTrue($plugin->isPipelineStopped());
    $plugin->reset();
    $this->assertFalse($plugin->isPipelineStopped());
  }

}

/**
 * Extends ProcessPluginBase as a non-abstract class.
 */
class ProcessPluginBase extends CoreProcessPluginBase {

}
