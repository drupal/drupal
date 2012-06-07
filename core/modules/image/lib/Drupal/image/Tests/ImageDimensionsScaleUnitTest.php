<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageDimensionsScaleUnitTest.
 */

namespace Drupal\image\Tests;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests image_dimensions_scale().
 */
class ImageDimensionsScaleUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'image_dimensions_scale()',
      'description' => 'Tests all control flow branches in image_dimensions_scale().',
      'group' => 'Image',
    );
  }

  /**
   * Tests all control flow branches in image_dimensions_scale().
   */
  function testImageDimensionsScale() {
    // Define input / output datasets to test different branch conditions.
    $test = array();

    // Test branch conditions:
    // - No height.
    // - Upscale, don't need to upscale.
    $tests[] = array(
      'input' => array(
        'dimensions' => array(
          'width' => 1000,
          'height' => 2000,
        ),
        'width' => 200,
        'height' => NULL,
        'upscale' => TRUE,
      ),
      'output' => array(
        'dimensions' => array(
          'width' => 200,
          'height' => 400,
        ),
        'return_value' => TRUE,
      ),
    );

    // Test branch conditions:
    // - No width.
    // - Don't upscale, don't need to upscale.
    $tests[] = array(
      'input' => array(
        'dimensions' => array(
          'width' => 1000,
          'height' => 800,
        ),
        'width' => NULL,
        'height' => 140,
        'upscale' => FALSE,
      ),
      'output' => array(
        'dimensions' => array(
          'width' => 175,
          'height' => 140,
        ),
        'return_value' => TRUE,
      ),
    );

    // Test branch conditions:
    // - Source aspect ratio greater than target.
    // - Upscale, need to upscale.
    $tests[] = array(
      'input' => array(
        'dimensions' => array(
          'width' => 8,
          'height' => 20,
        ),
        'width' => 200,
        'height' => 140,
        'upscale' => TRUE,
      ),
      'output' => array(
        'dimensions' => array(
          'width' => 56,
          'height' => 140,
        ),
        'return_value' => TRUE,
      ),
    );

    // Test branch condition: target aspect ratio greater than source.
    $tests[] = array(
      'input' => array(
        'dimensions' => array(
          'width' => 2000,
          'height' => 800,
        ),
        'width' => 200,
        'height' => 140,
        'upscale' => FALSE,
      ),
      'output' => array(
        'dimensions' => array(
          'width' => 200,
          'height' => 80,
        ),
        'return_value' => TRUE,
      ),
    );

    // Test branch condition: don't upscale, need to upscale.
    $tests[] = array(
      'input' => array(
        'dimensions' => array(
          'width' => 100,
          'height' => 50,
        ),
        'width' => 200,
        'height' => 140,
        'upscale' => FALSE,
      ),
      'output' => array(
        'dimensions' => array(
          'width' => 100,
          'height' => 50,
        ),
        'return_value' => FALSE,
      ),
    );

    foreach ($tests as $test) {
      // Process the test dataset.
      $return_value = image_dimensions_scale($test['input']['dimensions'], $test['input']['width'], $test['input']['height'], $test['input']['upscale']);

      // Check the width.
      $this->assertEqual($test['output']['dimensions']['width'], $test['input']['dimensions']['width'], t('Computed width (@computed_width) equals expected width (@expected_width)', array('@computed_width' => $test['output']['dimensions']['width'], '@expected_width' => $test['input']['dimensions']['width'])));

      // Check the height.
      $this->assertEqual($test['output']['dimensions']['height'], $test['input']['dimensions']['height'], t('Computed height (@computed_height) equals expected height (@expected_height)', array('@computed_height' => $test['output']['dimensions']['height'], '@expected_height' => $test['input']['dimensions']['height'])));

      // Check the return value.
      $this->assertEqual($test['output']['return_value'], $return_value, t('Correct return value.'));
    }
  }
}
