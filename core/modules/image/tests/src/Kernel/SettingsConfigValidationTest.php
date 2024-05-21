<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group image
 */
class SettingsConfigValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

  /**
   * Tests that the preview_image setting must be an existing image file.
   */
  public function testPreviewImagePathIsValidated(): void {
    $this->installConfig('image');

    // Drupal does not have a hard dependency on the fileinfo extension and
    // implements an extension-based mimetype guesser. Therefore, we must use
    // an incorrect extension here instead of writing text to a supposed PNG
    // file and depending on a check of the file contents.
    $file = sys_get_temp_dir() . '/fake_image.png.txt';
    file_put_contents($file, 'Not an image!');

    $this->expectException(SchemaIncompleteException::class);
    $this->expectExceptionMessage('[preview_image] This file is not a valid image.');
    $this->config('image.settings')
      ->set('preview_image', $file)
      ->save();
  }

}
