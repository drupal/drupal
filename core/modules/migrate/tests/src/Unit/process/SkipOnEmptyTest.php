<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\SkipOnEmpty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the skip on empty process plugin.
 */
#[CoversClass(SkipOnEmpty::class)]
#[Group('migrate')]
class SkipOnEmptyTest extends MigrateProcessTestCase {

  /**
   * Tests process skips on empty.
   *
   * @legacy-covers ::process
   */
  public function testProcessSkipsOnEmpty(): void {
    $configuration['method'] = 'process';
    $plugin = new SkipOnEmpty($configuration, 'skip_on_empty', []);
    $this->assertFalse($plugin->isPipelineStopped());
    $plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertTrue($plugin->isPipelineStopped());
  }

  /**
   * Tests process bypasses on non empty.
   *
   * @legacy-covers ::process
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
   * Tests row skips on empty.
   *
   * @legacy-covers ::row
   */
  public function testRowSkipsOnEmpty(): void {
    $configuration['method'] = 'row';
    $this->expectException(MigrateSkipRowException::class);
    (new SkipOnEmpty($configuration, 'skip_on_empty', []))
      ->transform('', $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests row bypasses on non empty.
   *
   * @legacy-covers ::row
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
   * @legacy-covers ::row
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
   * @legacy-covers ::row
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
   * Tests repeated execution of a process plugin resets the pipeline stoppage.
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
