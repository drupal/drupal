<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy deprecated functions in file.module.
 *
 * @group file
 * @group legacy
 */
class LegacyFileThemeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * @covers ::template_preprocess_file_upload_help
   */
  public function testTemplatePreprocessFileUploadHelp(): void {
    $variables['description'] = 'foo';
    $variables['cardinality'] = 1;
    $variables['upload_validators'] = [
      'file_validate_size' => [1000],
      'file_validate_extensions' => ['txt'],
      'file_validate_image_resolution' => ['100x100', '50x50'],
    ];

    $this->expectDeprecation('\'file_validate_size\' is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the \'FileSizeLimit\' constraint instead. See https://www.drupal.org/node/3363700');
    $this->expectDeprecation('\'file_validate_extensions\' is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the \'FileExtension\' constraint instead. See https://www.drupal.org/node/3363700');
    $this->expectDeprecation('\'file_validate_image_resolution\' is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the \'FileImageDimensions\' constraint instead. See https://www.drupal.org/node/3363700');

    template_preprocess_file_upload_help($variables);

    $this->assertCount(5, $variables['descriptions']);

    $descriptions = $variables['descriptions'];
    $this->assertEquals('foo', $descriptions[0]);
    $this->assertEquals('One file only.', $descriptions[1]);
    $this->assertEquals('1000 bytes limit.', $descriptions[2]);
    $this->assertEquals('Allowed types: txt.', $descriptions[3]);
    $this->assertEquals('Images must be larger than 50x50 pixels. Images larger than 100x100 pixels will be resized.', $descriptions[4]);
  }

}
