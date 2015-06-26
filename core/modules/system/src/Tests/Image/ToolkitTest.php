<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Image\ToolkitTest.
 */

namespace Drupal\system\Tests\Image;

/**
 * Tests image toolkit functions.
 *
 * @group Image
 */
class ToolkitTest extends ToolkitTestBase {
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
    $this->assertToolkitOperationsCalled(array('parseFile'));
  }

  /**
   * Test the image_save() function.
   */
  function testSave() {
    $this->assertFalse($this->image->save(), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(array('save'));
  }

  /**
   * Test the image_apply() function.
   */
  function testApply() {
    $data = array('p1' => 1, 'p2' => TRUE, 'p3' => 'text');
    $this->assertTrue($this->image->apply('my_operation', $data), 'Function returned the expected value.');

    // Check that apply was called and with the correct parameters.
    $this->assertToolkitOperationsCalled(array('apply'));
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['apply'][0][0], 'my_operation', "'my_operation' was passed correctly as operation");
    $this->assertEqual($calls['apply'][0][1]['p1'], 1, 'integer parameter p1 was passed correctly');
    $this->assertEqual($calls['apply'][0][1]['p2'], TRUE, 'boolean parameter p2 was passed correctly');
    $this->assertEqual($calls['apply'][0][1]['p3'], 'text', 'string parameter p3 was passed correctly');
  }

  /**
   * Test the image_apply() function.
   */
  function testApplyNoParameters() {
    $this->assertTrue($this->image->apply('my_operation'), 'Function returned the expected value.');

    // Check that apply was called and with the correct parameters.
    $this->assertToolkitOperationsCalled(array('apply'));
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['apply'][0][0], 'my_operation', "'my_operation' was passed correctly as operation");
    $this->assertEqual($calls['apply'][0][1], array(), 'passing no parameters was handled correctly');
  }
}
