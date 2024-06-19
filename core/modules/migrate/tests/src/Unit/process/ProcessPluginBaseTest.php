<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\ProcessPluginBase as CoreProcessPluginBase;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the base process plugin class.
 *
 * @group migrate
 *
 * @coversDefaultClass \Drupal\migrate\ProcessPluginBase
 */
class ProcessPluginBaseTest extends UnitTestCase {

  /**
   * Tests stopping the pipeline.
   *
   * @covers ::isPipelineStopped
   * @covers ::stopPipeline
   * @covers ::reset
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
