<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageEffectsTest.
 */

namespace Drupal\image\Tests;

use ImageToolkitTestCase;

/**
 * Use the image_test.module's mock toolkit to ensure that the effects are
 * properly passing parameters to the image toolkit.
 */
class ImageEffectsTest extends ImageToolkitTestCase {
  public static function getInfo() {
    return array(
      'name' => 'Image effects',
      'description' => 'Test that the image effects pass parameters to the toolkit correctly.',
      'group' => 'Image',
    );
  }

  function setUp() {
    parent::setUp('image_test');
    module_load_include('inc', 'image', 'image.effects');
  }

  /**
   * Test the image_resize_effect() function.
   */
  function testResizeEffect() {
    $this->assertTrue(image_resize_effect($this->image, array('width' => 1, 'height' => 2)), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['resize'][0][1], 1, t('Width was passed correctly'));
    $this->assertEqual($calls['resize'][0][2], 2, t('Height was passed correctly'));
  }

  /**
   * Test the image_scale_effect() function.
   */
  function testScaleEffect() {
    // @todo: need to test upscaling.
    $this->assertTrue(image_scale_effect($this->image, array('width' => 10, 'height' => 10)), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['resize'][0][1], 10, t('Width was passed correctly'));
    $this->assertEqual($calls['resize'][0][2], 5, t('Height was based off aspect ratio and passed correctly'));
  }

  /**
   * Test the image_crop_effect() function.
   */
  function testCropEffect() {
    // @todo should test the keyword offsets.
    $this->assertTrue(image_crop_effect($this->image, array('anchor' => 'top-1', 'width' => 3, 'height' => 4)), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('crop'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['crop'][0][1], 0, t('X was passed correctly'));
    $this->assertEqual($calls['crop'][0][2], 1, t('Y was passed correctly'));
    $this->assertEqual($calls['crop'][0][3], 3, t('Width was passed correctly'));
    $this->assertEqual($calls['crop'][0][4], 4, t('Height was passed correctly'));
  }

  /**
   * Test the image_scale_and_crop_effect() function.
   */
  function testScaleAndCropEffect() {
    $this->assertTrue(image_scale_and_crop_effect($this->image, array('width' => 5, 'height' => 10)), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('resize', 'crop'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['crop'][0][1], 7.5, t('X was computed and passed correctly'));
    $this->assertEqual($calls['crop'][0][2], 0, t('Y was computed and passed correctly'));
    $this->assertEqual($calls['crop'][0][3], 5, t('Width was computed and passed correctly'));
    $this->assertEqual($calls['crop'][0][4], 10, t('Height was computed and passed correctly'));
  }

  /**
   * Test the image_desaturate_effect() function.
   */
  function testDesaturateEffect() {
    $this->assertTrue(image_desaturate_effect($this->image, array()), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('desaturate'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual(count($calls['desaturate'][0]), 1, t('Only the image was passed.'));
  }

  /**
   * Test the image_rotate_effect() function.
   */
  function testRotateEffect() {
    // @todo: need to test with 'random' => TRUE
    $this->assertTrue(image_rotate_effect($this->image, array('degrees' => 90, 'bgcolor' => '#fff')), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('rotate'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['rotate'][0][1], 90, t('Degrees were passed correctly'));
    $this->assertEqual($calls['rotate'][0][2], 0xffffff, t('Background color was passed correctly'));
  }
}
