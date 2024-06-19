<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\KernelTests\KernelTestBase;

// cspell:ignore msword

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
  public function testFileProgressDeprecation(): void {
    $this->expectDeprecation('file_progress_implementation() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use extension_loaded(\'uploadprogress\') instead. See https://www.drupal.org/node/3397577');
    $this->assertFalse(\file_progress_implementation());
  }

  /**
   * @covers ::file_icon_map
   */
  public function testFileIconMapDeprecation(): void {
    $this->expectDeprecation('file_icon_map() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\IconMimeTypes::getGenericMimeType() instead. See https://www.drupal.org/node/3411269');
    $mimeType = \file_icon_map('application/msword');
    $this->assertEquals('x-office-document', $mimeType);
  }

  /**
   * @covers ::file_icon_class
   */
  public function testFileIconClassDeprecation(): void {
    $this->expectDeprecation('file_icon_class() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\IconMimeTypes::getIconClass() instead. See https://www.drupal.org/node/3411269');
    $iconClass = \file_icon_class('image/jpeg');
    $this->assertEquals('image', $iconClass);
  }

}
