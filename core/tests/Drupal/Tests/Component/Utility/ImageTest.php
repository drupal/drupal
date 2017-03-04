<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Image;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Utility\Image
 * @group Image
 */
class ImageTest extends UnitTestCase {

  /**
   * Tests all control flow branches in image_dimensions_scale().
   *
   * @dataProvider providerTestScaleDimensions
   */
  public function testScaleDimensions($input, $output) {
    // Process the test dataset.
    $return_value = Image::scaleDimensions($input['dimensions'], $input['width'], $input['height'], $input['upscale']);

    // Check the width.
    $this->assertEquals($output['dimensions']['width'], $input['dimensions']['width'], sprintf('Computed width (%s) does not equal expected width (%s)', $output['dimensions']['width'], $input['dimensions']['width']));

    // Check the height.
    $this->assertEquals($output['dimensions']['height'], $input['dimensions']['height'], sprintf('Computed height (%s) does not equal expected height (%s)', $output['dimensions']['height'], $input['dimensions']['height']));

    // Check the return value.
    $this->assertEquals($output['return_value'], $return_value, 'Incorrect return value.');
  }

  /**
   * Provides data for image dimension scale tests.
   *
   * @return array
   *   Keyed array containing:
   *   - 'input' - Array which contains input for Image::scaleDimensions().
   *   - 'output' - Array which contains expected output after passing
   *     through Image::scaleDimensions. Also contains a boolean
   *     'return_value' which should match the expected return value.
   *
   * @see testScaleDimensions()
   */
  public function providerTestScaleDimensions() {
    // Define input / output datasets to test different branch conditions.
    $tests = [];

    // Test branch conditions:
    // - No height.
    // - Upscale, don't need to upscale.
    $tests[] = [
      'input' => [
        'dimensions' => [
          'width' => 1000,
          'height' => 2000,
        ],
        'width' => 200,
        'height' => NULL,
        'upscale' => TRUE,
      ],
      'output' => [
        'dimensions' => [
          'width' => 200,
          'height' => 400,
        ],
        'return_value' => TRUE,
      ],
    ];

    // Test branch conditions:
    // - No width.
    // - Don't upscale, don't need to upscale.
    $tests[] = [
      'input' => [
        'dimensions' => [
          'width' => 1000,
          'height' => 800,
        ],
        'width' => NULL,
        'height' => 140,
        'upscale' => FALSE,
      ],
      'output' => [
        'dimensions' => [
          'width' => 175,
          'height' => 140,
        ],
        'return_value' => TRUE,
      ],
    ];

    // Test branch conditions:
    // - Source aspect ratio greater than target.
    // - Upscale, need to upscale.
    $tests[] = [
      'input' => [
        'dimensions' => [
          'width' => 8,
          'height' => 20,
        ],
        'width' => 200,
        'height' => 140,
        'upscale' => TRUE,
      ],
      'output' => [
        'dimensions' => [
          'width' => 56,
          'height' => 140,
        ],
        'return_value' => TRUE,
      ],
    ];

    // Test branch condition: target aspect ratio greater than source.
    $tests[] = [
      'input' => [
        'dimensions' => [
          'width' => 2000,
          'height' => 800,
        ],
        'width' => 200,
        'height' => 140,
        'upscale' => FALSE,
      ],
      'output' => [
        'dimensions' => [
          'width' => 200,
          'height' => 80,
        ],
        'return_value' => TRUE,
      ],
    ];

    // Test branch condition: don't upscale, need to upscale.
    $tests[] = [
      'input' => [
        'dimensions' => [
          'width' => 100,
          'height' => 50,
        ],
        'width' => 200,
        'height' => 140,
        'upscale' => FALSE,
      ],
      'output' => [
        'dimensions' => [
          'width' => 100,
          'height' => 50,
        ],
        'return_value' => FALSE,
      ],
    ];

    return $tests;
  }

}
