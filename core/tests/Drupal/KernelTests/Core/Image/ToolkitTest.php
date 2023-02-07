<?php

namespace Drupal\KernelTests\Core\Image;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\ImageToolkit\ImageToolkitInterface;
use Drupal\image\ImageEffectManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Traits\Core\Image\ToolkitTestTrait;

/**
 * Tests the image toolkit.
 *
 * @group Image
 */
class ToolkitTest extends KernelTestBase {

  use ToolkitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image_test',
    'system',
  ];

  /**
   * Testing image.
   *
   * @var \Drupal\Core\Image\ImageInterface
   */
  protected $image;

  /**
   * The image effect plugin manager service.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected ImageEffectManager $imageEffectPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->image = $this->getImage();
    $this->imageTestReset();
  }

  /**
   * Tests that the toolkit manager only returns available toolkits.
   */
  public function testGetAvailableToolkits() {
    $manager = $this->container->get('image.toolkit.manager');
    $toolkits = $manager->getAvailableToolkits();

    $this->assertArrayHasKey('test', $toolkits);
    $this->assertArrayHasKey('test:derived_toolkit', $toolkits);
    $this->assertArrayNotHasKey('broken', $toolkits);
    $this->assertToolkitOperationsCalled([]);
  }

  /**
   * Tests Image's methods.
   */
  public function testLoad() {
    $image = $this->getImage();
    $this->assertInstanceOf(ImageInterface::class, $image);
    $this->assertEquals('test', $image->getToolkitId());
    $this->assertToolkitOperationsCalled(['parseFile']);
  }

  /**
   * Tests the Image::save() function.
   */
  public function testSave() {
    $this->assertFalse($this->image->save());
    $this->assertToolkitOperationsCalled(['save']);
  }

  /**
   * Tests the 'apply' method.
   */
  public function testApply() {
    $data = ['p1' => 1, 'p2' => TRUE, 'p3' => 'text'];

    // The operation plugin itself does not exist, so apply will return false.
    $this->assertFalse($this->image->apply('my_operation', $data));

    // Check that apply was called and with the correct parameters.
    $this->assertToolkitOperationsCalled(['apply']);
    $calls = $this->imageTestGetAllCalls();
    $this->assertEquals('my_operation', $calls['apply'][0][0]);
    $this->assertEquals(1, $calls['apply'][0][1]['p1']);
    $this->assertTrue($calls['apply'][0][1]['p2']);
    $this->assertEquals('text', $calls['apply'][0][1]['p3']);
  }

  /**
   * Tests the 'apply' method without parameters.
   */
  public function testApplyNoParameters() {
    // The operation plugin itself does not exist, so apply will return false.
    $this->assertFalse($this->image->apply('my_operation'));

    // Check that apply was called and with the correct parameters.
    $this->assertToolkitOperationsCalled(['apply']);
    $calls = $this->imageTestGetAllCalls();
    $this->assertEquals('my_operation', $calls['apply'][0][0]);
    $this->assertSame([], $calls['apply'][0][1]);
  }

  /**
   * Tests image toolkit operations inheritance by derivative toolkits.
   */
  public function testDerivative() {
    $toolkit_manager = $this->container->get('image.toolkit.manager');
    $operation_manager = $this->container->get('image.toolkit.operation.manager');

    $toolkit = $toolkit_manager->createInstance('test:derived_toolkit');
    $this->assertInstanceOf(ImageToolkitInterface::class, $toolkit);

    // Load an overwritten and an inherited operation.
    $blur = $operation_manager->getToolkitOperation($toolkit, 'blur');
    $invert = $operation_manager->getToolkitOperation($toolkit, 'invert');

    // 'Blur' operation overwritten by derivative.
    $this->assertEquals('foo_derived', $blur->getPluginId());
    // "Invert" operation inherited from base plugin.
    $this->assertEquals('bar', $invert->getPluginId());
  }

  /**
   * Tests calling a failing image operation plugin.
   */
  public function testFailingOperation(): void {
    $this->assertFalse($this->image->apply('failing'));

    // Check that apply was called and with the correct parameters.
    $this->assertToolkitOperationsCalled(['apply']);
    $calls = $this->imageTestGetAllCalls();
    $this->assertEquals('failing', $calls['apply'][0][0]);
    $this->assertSame([], $calls['apply'][0][1]);
  }

}
