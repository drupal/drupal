<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\SkipRowIfNotSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the skip row if not set process plugin.
 */
#[CoversClass(SkipRowIfNotSet::class)]
#[Group('migrate')]
class SkipRowIfNotSetTest extends MigrateProcessTestCase {

  /**
   * Tests that a skip row exception without a message is raised.
   *
   * @legacy-covers ::transform
   */
  public function testRowSkipWithoutMessage(): void {
    $configuration = [
      'index' => 'some_key',
    ];
    $process = new SkipRowIfNotSet($configuration, 'skip_row_if_not_set', []);
    $this->expectException(MigrateSkipRowException::class);
    $process->transform('', $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests that a skip row exception with a message is raised.
   *
   * @legacy-covers ::transform
   */
  public function testRowSkipWithMessage(): void {
    $configuration = [
      'index' => 'some_key',
      'message' => "The 'some_key' key is not set",
    ];
    $process = new SkipRowIfNotSet($configuration, 'skip_row_if_not_set', []);
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("The 'some_key' key is not set");
    $process->transform('', $this->migrateExecutable, $this->row, 'destination_property');
  }

}
