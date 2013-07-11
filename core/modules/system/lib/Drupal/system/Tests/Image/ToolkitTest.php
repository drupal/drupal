<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Image\ToolkitTest.
 */

namespace Drupal\system\Tests\Image;

use Drupal\system\Plugin\ImageToolkitManager;

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
   * Check that ImageToolkitManager::getAvailableToolkits() only returns
   * available toolkits.
   */
  function testGetAvailableToolkits() {
    $manager = new ImageToolkitManager($this->container->get('container.namespaces'), $this->container->get('cache.cache'), $this->container->get('language_manager'));
    $toolkits = $manager->getAvailableToolkits();
    $this->assertTrue(isset($toolkits['test']), 'The working toolkit was returned.');
    $this->assertFalse(isset($toolkits['broken']), 'The toolkit marked unavailable was not returned');
    $this->assertToolkitOperationsCalled(array());
  }

  /**
   * Test the image_load() function.
   */
  function testLoad() {
    $image = image_load($this->file, $this->toolkit);
    $this->assertTrue(is_object($image), 'Returned an object.');
    $this->assertEqual($this->toolkit, $image->toolkit, 'Image had toolkit set.');
    $this->assertToolkitOperationsCalled(array('load', 'get_info'));
  }

  /**
   * Test the image_save() function.
   */
  function testSave() {
    $this->assertFalse(image_save($this->image), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('save'));
  }

  /**
   * Test the image_resize() function.
   */
  function testResize() {
    $this->assertTrue(image_resize($this->image, 1, 2), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['resize'][0][1], 1, 'Width was passed correctly');
    $this->assertEqual($calls['resize'][0][2], 2, 'Height was passed correctly');
  }

  /**
   * Test the image_scale() function.
   */
  function testScale() {
// TODO: need to test upscaling
    $this->assertTrue(image_scale($this->image, 10, 10), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['resize'][0][1], 10, 'Width was passed correctly');
    $this->assertEqual($calls['resize'][0][2], 5, 'Height was based off aspect ratio and passed correctly');
  }

  /**
   * Test the image_scale_and_crop() function.
   */
  function testScaleAndCrop() {
    $this->assertTrue(image_scale_and_crop($this->image, 5, 10), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('resize', 'crop'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();

    $this->assertEqual($calls['crop'][0][1], 7.5, 'X was computed and passed correctly');
    $this->assertEqual($calls['crop'][0][2], 0, 'Y was computed and passed correctly');
    $this->assertEqual($calls['crop'][0][3], 5, 'Width was computed and passed correctly');
    $this->assertEqual($calls['crop'][0][4], 10, 'Height was computed and passed correctly');
  }

  /**
   * Test the image_rotate() function.
   */
  function testRotate() {
    $this->assertTrue(image_rotate($this->image, 90, 1), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('rotate'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['rotate'][0][1], 90, 'Degrees were passed correctly');
    $this->assertEqual($calls['rotate'][0][2], 1, 'Background color was passed correctly');
  }

  /**
   * Test the image_crop() function.
   */
  function testCrop() {
    $this->assertTrue(image_crop($this->image, 1, 2, 3, 4), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('crop'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['crop'][0][1], 1, 'X was passed correctly');
    $this->assertEqual($calls['crop'][0][2], 2, 'Y was passed correctly');
    $this->assertEqual($calls['crop'][0][3], 3, 'Width was passed correctly');
    $this->assertEqual($calls['crop'][0][4], 4, 'Height was passed correctly');
  }

  /**
   * Test the image_desaturate() function.
   */
  function testDesaturate() {
    $this->assertTrue(image_desaturate($this->image), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('desaturate'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual(count($calls['desaturate'][0]), 1, 'Only the image was passed.');
  }
}
