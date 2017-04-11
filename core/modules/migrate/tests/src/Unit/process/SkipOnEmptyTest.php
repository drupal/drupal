<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateSkipProcessException;
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
  public function testProcessSkipsOnEmpty() {
    $configuration['method'] = 'process';
    $this->setExpectedException(MigrateSkipProcessException::class);
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
   */
  public function testRowSkipsOnEmpty() {
    $configuration['method'] = 'row';
    $this->setExpectedException(MigrateSkipRowException::class);
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

  /**
   * Tests that a skip row exception without a message is raised.
   *
   * @covers ::row
   */
  public function testRowSkipWithoutMessage() {
    $configuration = [
      'method' => 'row',
    ];
    $process = new SkipOnEmpty($configuration, 'skip_on_empty', []);
    $this->setExpectedException(MigrateSkipRowException::class);
    $process->transform('', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests that a skip row exception with a message is raised.
   *
   * @covers ::row
   */
  public function testRowSkipWithMessage() {
    $configuration = [
      'method' => 'row',
      'message' => 'The value is empty',
    ];
    $process = new SkipOnEmpty($configuration, 'skip_on_empty', []);
    $this->setExpectedException(MigrateSkipRowException::class, 'The value is empty');
    $process->transform('', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
