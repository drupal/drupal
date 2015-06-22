<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\SkipOnEmptyTest.
 */


namespace Drupal\Tests\migrate\Unit\process;
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
   * @expectedException \Drupal\migrate\MigrateSkipProcessException
   */
  public function testProcessSkipsOnEmpty() {
    $configuration['method'] = 'process';
    (new SkipOnEmpty($configuration, 'skip_on_empty', []))
      ->transform('', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::process
   */
  public function testProcessBypassesOnNonEmpty() {
    $configuration['method'] = 'process';
    $value = (new SkipOnEmpty($configuration, 'skip_on_empty', []))
      ->transform(' ', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, ' ');
  }

  /**
   * @covers ::row
   * @expectedException \Drupal\migrate\MigrateSkipRowException
   */
  public function testRowSkipsOnEmpty() {
    $configuration['method'] = 'row';
    (new SkipOnEmpty($configuration, 'skip_on_empty', []))
      ->transform('', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::row
   */
  public function testRowBypassesOnNonEmpty() {
    $configuration['method'] = 'row';
    $value = (new SkipOnEmpty($configuration, 'skip_on_empty', []))
      ->transform(' ', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, ' ');
  }

}
