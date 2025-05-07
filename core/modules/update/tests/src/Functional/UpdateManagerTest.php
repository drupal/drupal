<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests legacy Update Manager functionality of the Update Status module.
 *
 * @group legacy
 * @group update
 */
class UpdateManagerTest extends UpdateTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Checks that clearing the disk cache works.
   */
  public function testClearDiskCache(): void {
    $directories = [
      _update_manager_cache_directory(FALSE),
      _update_manager_extract_directory(FALSE),
    ];
    // Check that update directories does not exists.
    foreach ($directories as $directory) {
      $this->assertDirectoryDoesNotExist($directory);
    }

    // Method must not fail if update directories do not exists.
    update_clear_update_disk_cache();
  }

}
