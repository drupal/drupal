<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Image\ToolkitTest.
 */

namespace Drupal\system\Tests\Image;

/**
 * Test that the functions in image.inc correctly pass data to the toolkit.
 */
class ToolkitTest extends ToolkitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Image toolkit tests',
      'description' => 'Check image toolkit functions.',
      'group' => 'Image',
    );
  }

  /**
   * Check that hook_image_toolkits() is called and only available toolkits are
   * returned.
   */
  function testGetAvailableToolkits() {
    $toolkits = image_get_available_toolkits();
    $this->assertTrue(isset($toolkits['test']), t('The working toolkit was returned.'));
    $this->assertFalse(isset($toolkits['broken']), t('The toolkit marked unavailable was not returned'));
    $this->assertToolkitOperationsCalled(array());
  }

  /**
   * Test the image_load() function.
   */
  function testLoad() {
    $image = image_load($this->file, $this->toolkit);
    $this->assertTrue(is_object($image), t('Returned an object.'));
    $this->assertEqual($this->toolkit, $image->toolkit, t('Image had toolkit set.'));
    $this->assertToolkitOperationsCalled(array('load', 'get_info'));
  }

  /**
   * Test the image_save() function.
   */
  function testSave() {
    $this->assertFalse(image_save($this->image), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('save'));
  }

  /**
   * Test the image_resize() function.
   */
  function testResize() {
    $this->assertTrue(image_resize($this->image, 1, 2), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['resize'][0][1], 1, t('Width was passed correctly'));
    $this->assertEqual($calls['resize'][0][2], 2, t('Height was passed correctly'));
  }

  /**
   * Test the image_scale() function.
   */
  function testScale() {
// TODO: need to test upscaling
    $this->assertTrue(image_scale($this->image, 10, 10), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['resize'][0][1], 10, t('Width was passed correctly'));
    $this->assertEqual($calls['resize'][0][2], 5, t('Height was based off aspect ratio and passed correctly'));
  }

  /**
   * Test the image_scale_and_crop() function.
   */
  function testScaleAndCrop() {
    $this->assertTrue(image_scale_and_crop($this->image, 5, 10), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('resize', 'crop'));

    // Check the parameters.
    $calls = image_test_get_all_calls();

    $this->assertEqual($calls['crop'][0][1], 7.5, t('X was computed and passed correctly'));
    $this->assertEqual($calls['crop'][0][2], 0, t('Y was computed and passed correctly'));
    $this->assertEqual($calls['crop'][0][3], 5, t('Width was computed and passed correctly'));
    $this->assertEqual($calls['crop'][0][4], 10, t('Height was computed and passed correctly'));
  }

  /**
   * Test the image_rotate() function.
   */
  function testRotate() {
    $this->assertTrue(image_rotate($this->image, 90, 1), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('rotate'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['rotate'][0][1], 90, t('Degrees were passed correctly'));
    $this->assertEqual($calls['rotate'][0][2], 1, t('Background color was passed correctly'));
  }

  /**
   * Test the image_crop() function.
   */
  function testCrop() {
    $this->assertTrue(image_crop($this->image, 1, 2, 3, 4), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('crop'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual($calls['crop'][0][1], 1, t('X was passed correctly'));
    $this->assertEqual($calls['crop'][0][2], 2, t('Y was passed correctly'));
    $this->assertEqual($calls['crop'][0][3], 3, t('Width was passed correctly'));
    $this->assertEqual($calls['crop'][0][4], 4, t('Height was passed correctly'));
  }

  /**
   * Test the image_desaturate() function.
   */
  function testDesaturate() {
    $this->assertTrue(image_desaturate($this->image), t('Function returned the expected value.'));
    $this->assertToolkitOperationsCalled(array('desaturate'));

    // Check the parameters.
    $calls = image_test_get_all_calls();
    $this->assertEqual(count($calls['desaturate'][0]), 1, t('Only the image was passed.'));
  }
}
