<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageEffectsTest.
 */

namespace Drupal\image\Tests;

use Drupal\system\Tests\Image\ToolkitTestBase;

/**
 * Use the image_test.module's mock toolkit to ensure that the effects are
 * properly passing parameters to the image toolkit.
 */
class ImageEffectsTest extends ToolkitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image', 'image_test', 'image_module_test');

  public static function getInfo() {
    return array(
      'name' => 'Image effects',
      'description' => 'Test that the image effects pass parameters to the toolkit correctly.',
      'group' => 'Image',
    );
  }

  function setUp() {
    parent::setUp();

    module_load_include('inc', 'image', 'image.effects');
  }

  /**
   * Test the image_resize_effect() function.
   */
  function testResizeEffect() {
    $this->assertTrue(image_resize_effect($this->image, array('width' => 1, 'height' => 2)), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['resize'][0][1], 1, 'Width was passed correctly');
    $this->assertEqual($calls['resize'][0][2], 2, 'Height was passed correctly');
  }

  /**
   * Test the image_scale_effect() function.
   */
  function testScaleEffect() {
    // @todo: need to test upscaling.
    $this->assertTrue(image_scale_effect($this->image, array('width' => 10, 'height' => 10)), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['resize'][0][1], 10, 'Width was passed correctly');
    $this->assertEqual($calls['resize'][0][2], 5, 'Height was based off aspect ratio and passed correctly');
  }

  /**
   * Test the image_crop_effect() function.
   */
  function testCropEffect() {
    // @todo should test the keyword offsets.
    $this->assertTrue(image_crop_effect($this->image, array('anchor' => 'top-1', 'width' => 3, 'height' => 4)), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('crop'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['crop'][0][1], 0, 'X was passed correctly');
    $this->assertEqual($calls['crop'][0][2], 1, 'Y was passed correctly');
    $this->assertEqual($calls['crop'][0][3], 3, 'Width was passed correctly');
    $this->assertEqual($calls['crop'][0][4], 4, 'Height was passed correctly');
  }

  /**
   * Test the image_scale_and_crop_effect() function.
   */
  function testScaleAndCropEffect() {
    $this->assertTrue(image_scale_and_crop_effect($this->image, array('width' => 5, 'height' => 10)), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('resize', 'crop'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['crop'][0][1], 7.5, 'X was computed and passed correctly');
    $this->assertEqual($calls['crop'][0][2], 0, 'Y was computed and passed correctly');
    $this->assertEqual($calls['crop'][0][3], 5, 'Width was computed and passed correctly');
    $this->assertEqual($calls['crop'][0][4], 10, 'Height was computed and passed correctly');
  }

  /**
   * Test the image_desaturate_effect() function.
   */
  function testDesaturateEffect() {
    $this->assertTrue(image_desaturate_effect($this->image, array()), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('desaturate'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual(count($calls['desaturate'][0]), 1, 'Only the image was passed.');
  }

  /**
   * Test the image_rotate_effect() function.
   */
  function testRotateEffect() {
    // @todo: need to test with 'random' => TRUE
    $this->assertTrue(image_rotate_effect($this->image, array('degrees' => 90, 'bgcolor' => '#fff')), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('rotate'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['rotate'][0][1], 90, 'Degrees were passed correctly');
    $this->assertEqual($calls['rotate'][0][2], 0xffffff, 'Background color was passed correctly');
  }

  /**
   * Test image effect caching.
   */
  function testImageEffectsCaching() {
    $image_effect_definitions_called = &drupal_static('image_module_test_image_effect_info_alter');

    // First call should grab a fresh copy of the data.
    $effects = image_effect_definitions();
    $this->assertTrue($image_effect_definitions_called === 1, 'image_effect_definitions() generated data.');

    // Second call should come from cache.
    drupal_static_reset('image_effect_definitions');
    drupal_static_reset('image_module_test_image_effect_info_alter');
    $cached_effects = image_effect_definitions();
    $this->assertTrue(is_null($image_effect_definitions_called), 'image_effect_definitions() returned data from cache.');

    $this->assertTrue($effects == $cached_effects, 'Cached effects are the same as generated effects.');
  }
}
