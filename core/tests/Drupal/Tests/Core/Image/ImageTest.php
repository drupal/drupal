<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Image\ImageTest.
 */

namespace Drupal\Tests\Core\Image;

use Drupal\Core\Image\Image;
use Drupal\Core\ImageToolkit\ImageToolkitInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the image class.
 *
 * @group Image
 */
class ImageTest extends UnitTestCase {

  /**
   * Image source path.
   *
   * @var string
   */
  protected $source;

  /**
   * Image object.
   *
   * @var \Drupal\Core\Image\Image
   */
  protected $image;

  /**
   * Mocked image toolkit.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface
   */
  protected $toolkit;

  /**
   * Mocked image toolkit operation.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitOperationInterface
   */
  protected $toolkitOperation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Use the Druplicon image.
    $this->source = __DIR__ . '/../../../../../misc/druplicon.png';
  }

  /**
   * Mocks a toolkit.
   *
   * @param array $stubs
   *   (optional) Array containing methods to be replaced with stubs.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  protected function getToolkitMock(array $stubs = array()) {
    $mock_builder = $this->getMockBuilder('Drupal\system\Plugin\ImageToolkit\GDToolkit');
    $stubs = array_merge(array('getPluginId', 'save'), $stubs);
    return $mock_builder
      ->disableOriginalConstructor()
      ->setMethods($stubs)
      ->getMock();
  }

  /**
   * Mocks a toolkit operation.
   *
   * @param string $class_name
   *   The name of the GD toolkit operation class to be mocked.
   * @param ImageToolkitInterface $toolkit
   *   The image toolkit object.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  protected function getToolkitOperationMock($class_name, ImageToolkitInterface $toolkit) {
    $mock_builder = $this->getMockBuilder('Drupal\system\Plugin\ImageToolkit\Operation\gd\\' . $class_name);
    $logger = $this->getMock('Psr\Log\LoggerInterface');
    return $mock_builder
      ->setMethods(array('execute'))
      ->setConstructorArgs(array(array(), '', array(), $toolkit, $logger))
      ->getMock();
  }

  /**
   * Get an image with a mocked toolkit, for testing.
   *
   * @param bool $load_expected
   *   (optional) Whether the load() method is expected to be called. Defaults
   *   to TRUE.
   * @param array $stubs
   *   (optional) Array containing toolkit methods to be replaced with stubs.
   *
   * @return \Drupal\Core\Image\Image
   *   An image object.
   */
  protected function getTestImage($load_expected = TRUE, array $stubs = array()) {
    if (!$load_expected && !in_array('load', $stubs)) {
      $stubs = array_merge(array('load'), $stubs);
    }

    $this->toolkit = $this->getToolkitMock($stubs);

    $this->toolkit->expects($this->any())
      ->method('getPluginId')
      ->will($this->returnValue('gd'));

    if (!$load_expected) {
      $this->toolkit->expects($this->never())
        ->method('load');
    }

    $this->image = new Image($this->toolkit, $this->source);
  }

  /**
   * Get an image with mocked toolkit and operation, for operation testing.
   *
   * @param string $class_name
   *   The name of the GD toolkit operation class to be mocked.
   *
   * @return \Drupal\Core\Image\Image
   *   An image object.
   */
  protected function getTestImageForOperation($class_name) {
    $this->toolkit = $this->getToolkitMock(array('getToolkitOperation'));
    $this->toolkitOperation = $this->getToolkitOperationMock($class_name, $this->toolkit);

    $this->toolkit->expects($this->any())
      ->method('getPluginId')
      ->will($this->returnValue('gd'));

    $this->toolkit->expects($this->any())
      ->method('getToolkitOperation')
      ->will($this->returnValue($this->toolkitOperation));

    $this->image = new Image($this->toolkit, $this->source);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getHeight().
   */
  public function testGetHeight() {
    $this->getTestImage(FALSE);
    $this->assertEquals(100, $this->image->getHeight());
  }

  /**
   * Tests \Drupal\Core\Image\Image::getWidth().
   */
  public function testGetWidth() {
    $this->getTestImage(FALSE);
    $this->assertEquals(88, $this->image->getWidth());
  }

  /**
   * Tests \Drupal\Core\Image\Image::getFileSize
   */
  public function testGetFileSize() {
    $this->getTestImage(FALSE);
    $this->assertEquals(3905, $this->image->getFileSize());
  }

  /**
   * Tests \Drupal\Core\Image\Image::getToolkit()->getType().
   */
  public function testGetType() {
    $this->getTestImage(FALSE);
    $this->assertEquals(IMAGETYPE_PNG, $this->image->getToolkit()->getType());
  }

  /**
   * Tests \Drupal\Core\Image\Image::getMimeType().
   */
  public function testGetMimeType() {
    $this->getTestImage(FALSE);
    $this->assertEquals('image/png', $this->image->getMimeType());
  }

  /**
   * Tests \Drupal\Core\Image\Image::isValid().
   */
  public function testIsValid() {
    $this->getTestImage(FALSE);
    $this->assertTrue($this->image->isValid());
    $this->assertTrue(is_readable($this->image->getSource()));
  }

  /**
   * Tests \Drupal\Core\Image\Image::getToolkitId().
   */
  public function testGetToolkitId() {
    $this->getTestImage(FALSE);
    $this->assertEquals('gd', $this->image->getToolkitId());
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testSave() {
    $this->getTestImage();
    // This will fail if save() method isn't called on the toolkit.
    $toolkit = $this->getToolkitMock();
    $toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $image = $this->getMock('Drupal\Core\Image\Image', array('chmod'), array($toolkit, $this->image->getSource()));
    $image->expects($this->any())
      ->method('chmod')
      ->will($this->returnValue(TRUE));

    $image->save();
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testSaveFails() {
    $this->getTestImage();
    // This will fail if save() method isn't called on the toolkit.
    $this->toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(FALSE));

    $this->assertFalse($this->image->save());
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testChmodFails() {
    $this->getTestImage();
    // This will fail if save() method isn't called on the toolkit.
    $toolkit = $this->getToolkitMock();
    $toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $image = $this->getMock('Drupal\Core\Image\Image', array('chmod'), array($toolkit, $this->image->getSource()));
    $image->expects($this->any())
      ->method('chmod')
      ->will($this->returnValue(FALSE));

    $this->assertFalse($image->save());
  }

  /**
   * Tests \Drupal\Core\Image\Image::parseFile().
   */
  public function testParseFileFails() {
    $toolkit = $this->getToolkitMock();
    $image = new Image($toolkit, 'magic-foobars.png');

    $this->assertFalse($image->isValid());
    $this->assertFalse($image->save());
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleWidth() {
    $this->getTestImageForOperation('Scale');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->scale(44, NULL, FALSE);
    $this->assertEquals(50, $ret['height']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleHeight() {
    $this->getTestImageForOperation('Scale');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->scale(NULL, 50, FALSE);
    $this->assertEquals(44, $ret['width']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleSame() {
    $this->getTestImageForOperation('Scale');
    // Dimensions are the same, resize should not be called.
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->scale(88, 100, FALSE);
    $this->assertEquals(88, $ret['width']);
    $this->assertEquals(100, $ret['height']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropWidth() {
    $this->getTestImageForOperation('ScaleAndCrop');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->scaleAndCrop(34, 50, FALSE);
    $this->assertEquals(5, $ret['x']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropHeight() {
    $this->getTestImageForOperation('ScaleAndCrop');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->scaleAndCrop(44, 40);
    $this->assertEquals(5, $ret['y']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropFails() {
    $this->getTestImageForOperation('ScaleAndCrop');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->scaleAndCrop(44, 40);
    $this->assertEquals(0, $ret['x']);
    $this->assertEquals(5, $ret['y']);
    $this->assertEquals(44, $ret['resize']['width']);
    $this->assertEquals(50, $ret['resize']['height']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   */
  public function testCropWidth() {
    $this->getTestImageForOperation('Crop');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    // Cropping with width only should preserve the aspect ratio.
    $ret = $this->image->crop(0, 0, 44);
    $this->assertEquals(50, $ret['height']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   */
  public function testCropHeight() {
    $this->getTestImageForOperation('Crop');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    // Cropping with height only should preserve the aspect ratio.
    $ret = $this->image->crop(0, 0, NULL, 50);
    $this->assertEquals(44, $ret['width']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   */
  public function testCrop() {
    $this->getTestImageForOperation('Crop');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->crop(0, 0, 44, 50);
    $this->assertEquals(44, $ret['width']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::convert().
   */
  public function testConvert() {
    $this->getTestImageForOperation('Convert');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->convert('png');
    $this->assertEquals('png', $ret['extension']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::resize().
   */
  public function testResize() {
    $this->getTestImageForOperation('Resize');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    // Resize with integer for width and height.
    $ret = $this->image->resize(30, 40);
    $this->assertEquals(30, $ret['width']);
    $this->assertEquals(40, $ret['height']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::resize().
   */
  public function testFloatResize() {
    $this->getTestImageForOperation('Resize');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    // Pass a float for width.
    $ret = $this->image->resize(30.4, 40);
    // Ensure that the float was rounded to an integer first.
    $this->assertEquals(30, $ret['width']);
  }

  /**
   * Tests \Drupal\Core\Image\Image::desaturate().
   */
  public function testDesaturate() {
    $this->getTestImageForOperation('Desaturate');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $this->image->desaturate();
  }

  /**
   * Tests \Drupal\Core\Image\Image::rotate().
   */
  public function testRotate() {
    $this->getTestImageForOperation('Rotate');
    $this->toolkitOperation->expects($this->once())
      ->method('execute')
      ->will($this->returnArgument(0));

    $ret = $this->image->rotate(90);
    $this->assertEquals(90, $ret['degrees']);
  }

}
