<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Allows tests to alter dumps after they have loaded.
 *
 * @see \Drupal\migrate_drupal\Tests\d6\MigrateFileTest
 */
interface MigrateDumpAlterInterface {

  /**
   * Allows tests to alter dumps after they have loaded.
   *
   * @param \Drupal\KernelTests\KernelTestBase $test
   *   The test that is being run.
   */
  public static function migrateDumpAlter(KernelTestBase $test);

}
