<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Image\ToolkitTest.
 */

namespace Drupal\system\Tests\Image;

/**
 * Tests that the methods in Image correctly pass data to the toolkit.
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
    $manager = $this->container->get('image.toolkit.manager');
    $toolkits = $manager->getAvailableToolkits();
    $this->assertTrue(isset($toolkits['test']), 'The working toolkit was returned.');
    $this->assertFalse(isset($toolkits['broken']), 'The toolkit marked unavailable was not returned');
    $this->assertToolkitOperationsCalled(array());
  }

  /**
   * Tests Image's methods.
   */
  function testLoad() {
    $image = $this->getImage();
    $this->assertTrue(is_object($image), 'Returned an object.');
    $this->assertEqual($image->getToolkitId(), 'test', 'Image had toolkit set.');
    $this->assertToolkitOperationsCalled(array('load', 'get_info'));
  }

  /**
   * Test the image_save() function.
   */
  function testSave() {
    $this->assertFalse($this->image->save(), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('save'));
  }

  /**
   * Test the image_resize() function.
   */
  function testResize() {
    $this->assertTrue($this->image->resize(1, 2), 'Function returned the expected value.');
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
    $this->assertTrue($this->image->scale(10, 10), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('scale'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['scale'][0][1], 10, 'Width was passed correctly');
    $this->assertEqual($calls['scale'][0][2], 10, 'Height was based off aspect ratio and passed correctly');
  }

  /**
   * Test the image_scale_and_crop() function.
   */
  function testScaleAndCrop() {
    $this->assertTrue($this->image->scaleAndCrop(5, 10), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('scaleAndCrop'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();

    $this->assertEqual($calls['scaleAndCrop'][0][1], 5, 'Width was computed and passed correctly');
    $this->assertEqual($calls['scaleAndCrop'][0][2], 10, 'Height was computed and passed correctly');
  }

  /**
   * Test the image_rotate() function.
   */
  function testRotate() {
    $this->assertTrue($this->image->rotate(90, 1), 'Function returned the expected value.');
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
    $this->assertTrue($this->image->crop(1, 2, 3, 4), 'Function returned the expected value.');
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
    $this->assertTrue($this->image->desaturate(), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('desaturate'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual(count($calls['desaturate'][0]), 1, 'Only the image was passed.');
  }
}
