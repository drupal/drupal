<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests file module deprecations.
 *
 * @group legacy
 * @group file
 */
class LegacyFileModuleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * @covers ::file_progress_implementation
   */
  public function testFileProgressDeprecation() {
    $this->expectDeprecation('file_progress_implementation() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use extension_loaded(\'uploadprogress\') instead. See https://www.drupal.org/node/3397577');
    $this->assertFalse(\file_progress_implementation());
  }

}
