<?php

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
  public function testGetAvailableToolkits() {
    $manager = $this->container->get('image.toolkit.manager');
    $toolkits = $manager->getAvailableToolkits();
    $this->assertTrue(isset($toolkits['test']), 'The working toolkit was returned.');
    $this->assertTrue(isset($toolkits['test:derived_toolkit']), 'The derived toolkit was returned.');
    $this->assertFalse(isset($toolkits['broken']), 'The toolkit marked unavailable was not returned');
    $this->assertToolkitOperationsCalled([]);
  }

  /**
   * Tests Image's methods.
   */
  public function testLoad() {
    $image = $this->getImage();
    $this->assertTrue(is_object($image), 'Returned an object.');
    $this->assertEqual($image->getToolkitId(), 'test', 'Image had toolkit set.');
    $this->assertToolkitOperationsCalled(['parseFile']);
  }

  /**
   * Test the image_save() function.
   */
  public function testSave() {
    $this->assertFalse($this->image->save(), 'Function returned the expected value.');
    $this->assertToolkitOperationsCalled(['save']);
  }

  /**
   * Test the image_apply() function.
   */
  public function testApply() {
    $data = ['p1' => 1, 'p2' => TRUE, 'p3' => 'text'];
    $this->assertTrue($this->image->apply('my_operation', $data), 'Function returned the expected value.');

    // Check that apply was called and with the correct parameters.
    $this->assertToolkitOperationsCalled(['apply']);
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['apply'][0][0], 'my_operation', "'my_operation' was passed correctly as operation");
    $this->assertEqual($calls['apply'][0][1]['p1'], 1, 'integer parameter p1 was passed correctly');
    $this->assertEqual($calls['apply'][0][1]['p2'], TRUE, 'boolean parameter p2 was passed correctly');
    $this->assertEqual($calls['apply'][0][1]['p3'], 'text', 'string parameter p3 was passed correctly');
  }

  /**
   * Test the image_apply() function.
   */
  public function testApplyNoParameters() {
    $this->assertTrue($this->image->apply('my_operation'), 'Function returned the expected value.');

    // Check that apply was called and with the correct parameters.
    $this->assertToolkitOperationsCalled(['apply']);
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['apply'][0][0], 'my_operation', "'my_operation' was passed correctly as operation");
    $this->assertEqual($calls['apply'][0][1], [], 'passing no parameters was handled correctly');
  }

  /**
   * Tests image toolkit operations inheritance by derivative toolkits.
   */
  public function testDerivative() {
    $toolkit_manager = $this->container->get('image.toolkit.manager');
    $operation_manager = $this->container->get('image.toolkit.operation.manager');

    $toolkit = $toolkit_manager->createInstance('test:derived_toolkit');

    // Load an overwritten and an inherited operation.
    $blur = $operation_manager->getToolkitOperation($toolkit, 'blur');
    $invert = $operation_manager->getToolkitOperation($toolkit, 'invert');

    $this->assertIdentical('foo_derived', $blur->getPluginId(), "'Blur' operation overwritten by derivative.");
    $this->assertIdentical('bar', $invert->getPluginId(), '"Invert" operation inherited from base plugin.');
  }

}
