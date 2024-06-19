<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\SkipOnEmpty;

/**
 * Tests the skip on empty process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\SkipOnEmpty
 */
class SkipOnEmptyTest extends MigrateProcessTestCase {

  /**
   * @covers ::process
   */
  public function testProcessSkipsOnEmpty(): void {
    $configuration['method'] = 'process';
    $plugin = new SkipOnEmpty($configuration, 'skip_on_empty', []);
    $this->assertFalse($plugin->isPipelineStopped());
    $plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertTrue($plugin->isPipelineStopped());
  }

  /**
   * @covers ::process
   */
  public function testProcessBypassesOnNonEmpty(): void {
    $configuration['method'] = 'process';
    $plugin = new SkipOnEmpty($configuration, 'skip_on_empty', []);
    $value = $plugin
      ->transform(' ', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(' ', $value);
    $this->assertFalse($plugin->isPipelineStopped());
  }

  /**
   * @covers ::row
   */
  public function testRowSkipsOnEmpty(): void {
    $configuration['method'] = 'row';
    $this->expectException(MigrateSkipRowException::class);
    (new SkipOnEmpty($configuration, 'skip_on_empty', []))
      ->transform('', $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * @covers ::row
   */
  public function testRowBypassesOnNonEmpty(): void {
    $configuration['method'] = 'row';
    $value = (new SkipOnEmpty($configuration, 'skip_on_empty', []))
      ->transform(' ', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(' ', $value);
  }

  /**
   * Tests that a skip row exception without a message is raised.
   *
   * @covers ::row
   */
  public function testRowSkipWithoutMessage(): void {
    $configuration = [
      'method' => 'row',
    ];
    $process = new SkipOnEmpty($configuration, 'skip_on_empty', []);
    $this->expectException(MigrateSkipRowException::class);
    $process->transform('', $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests that a skip row exception with a message is raised.
   *
   * @covers ::row
   */
  public function testRowSkipWithMessage(): void {
    $configuration = [
      'method' => 'row',
      'message' => 'The value is empty',
    ];
    $process = new SkipOnEmpty($configuration, 'skip_on_empty', []);
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage('The value is empty');
    $process->transform('', $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests repeated execution of a process plugin can reset the pipeline stoppage correctly.
   */
  public function testMultipleTransforms(): void {
    $configuration['method'] = 'process';
    $plugin = new SkipOnEmpty($configuration, 'skip_on_empty', []);

    // Confirm transform will stop the pipeline.
    $value = $plugin
      ->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertNull($value);
    $this->assertTrue($plugin->isPipelineStopped());

    // Restart the pipeline and test again.
    $plugin->reset();
    $this->assertFalse($plugin->isPipelineStopped());
    $value = $plugin
      ->transform(' ', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(' ', $value);
    $this->assertFalse($plugin->isPipelineStopped());
  }

}
