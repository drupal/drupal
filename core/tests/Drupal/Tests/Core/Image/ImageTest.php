<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Image\ImageTest.
 */

namespace Drupal\Tests\Core\Image;

use Drupal\Core\Image\Image;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the image class.
 */
class ImageTest extends UnitTestCase {

  /**
   * Image object.
   *
   * @var \Drupal\Core\Image\Image
   */
  protected $image;

  /**
   * Image toolkit.
   *
   * @var \Drupal\system\Plugin\ImageToolkitInterface
   */
  protected $toolkit;

  public static function getInfo() {
    return array(
      'name' => 'Image class functionality',
      'description' => 'Tests the Image class.',
      'group' => 'Image',
    );
  }

  protected function setUp() {
    // Use the Druplicon image.
    $source = __DIR__ . '/../../../../../misc/druplicon.png';
    $this->toolkit = $this->getMockBuilder('Drupal\system\Plugin\ImageToolkit\GDToolkit')
      ->disableOriginalConstructor()
      ->getMock();

    $this->toolkit->expects($this->any())
      ->method('getPluginId')
      ->will($this->returnValue('gd'));

    $this->toolkit->expects($this->any())
      ->method('getInfo')
      ->will($this->returnValue(array(
        'width'     => 88,
        'height'    => 100,
        'extension' => 'png',
        'type'      => IMAGETYPE_PNG,
        'mime_type' => 'image/png',
      )));

    $this->image = new Image($source, $this->toolkit);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getExtension().
   */
  public function testGetExtension() {
    $this->assertEquals($this->image->getExtension(), 'png');
  }

  /**
   * Tests \Drupal\Core\Image\Image::getHeight().
   */
  public function testGetHeight() {
    $this->assertEquals($this->image->getHeight(), 100);
  }

  /**
   * Tests \Drupal\Core\Image\Image::setHeight().
   */
  public function testSetHeight() {
    $this->image->getHeight();
    $this->image->setHeight(400);
    $this->assertEquals($this->image->getHeight(), 400);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getWidth().
   */
  public function testGetWidth() {
    $this->assertEquals($this->image->getWidth(), 88);
  }

  /**
   * Tests \Drupal\Core\Image\Image::setWidth().
   */
  public function testSetWidth() {
    $this->image->getHeight();
    $this->image->setWidth(337);
    $this->assertEquals($this->image->getWidth(), 337);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getFileSize
   */
  public function testGetFileSize() {
    $this->assertEquals($this->image->getFileSize(), 3905);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getType().
   */
  public function testGetType() {
    $this->assertEquals($this->image->getType(), IMAGETYPE_PNG);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getMimeType().
   */
  public function testGetMimeType() {
    $this->assertEquals($this->image->getMimeType(), 'image/png');
  }

  /**
   * Tests \Drupal\Core\Image\Image::setResource().
   */
  public function testSetResource() {
    $resource = fopen($this->image->getSource(), 'r');
    $this->image->setResource($resource);
    $this->assertEquals($this->image->getResource(), $resource);

    // Force \Drupal\Core\Image\Image::hasResource() to return FALSE.
    $this->image->setResource(FALSE);
    $this->assertNotNull($this->image->getResource());
  }

  /**
   * Tests \Drupal\Core\Image\Image::hasResource().
   */
  public function testHasResource() {
    $this->assertFalse($this->image->hasResource());
    $resource = fopen($this->image->getSource(), 'r');
    $this->image->setResource($resource);
    $this->assertTrue($this->image->hasResource());
  }

  /**
   * Tests \Drupal\Core\Image\Image::setSource().
   */
  public function testSetSource() {
    $source = __DIR__ . '/../../../../../misc/grippie.png';
    $this->image->setSource($source);
    $this->assertEquals($this->image->getSource(), $source);
  }

  /**
   * Tests \Drupal\Core\Image\Image::getToolkitId().
   */
  public function testGetToolkitId() {
    $this->assertEquals($this->image->getToolkitId(), 'gd');
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testSave() {
    // This will fail if save() method isn't called on the toolkit.
    $this->toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $image = $this->getMock('Drupal\Core\Image\Image', array('chmod'), array($this->image->getSource(), $this->toolkit));
    $image->expects($this->any())
      ->method('chmod')
      ->will($this->returnValue(TRUE));

    $image->save();
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testSaveFails() {
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
    // This will fail if save() method isn't called on the toolkit.
    $this->toolkit->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $image = $this->getMock('Drupal\Core\Image\Image', array('chmod'), array($this->image->getSource(), $this->toolkit));
    $image->expects($this->any())
      ->method('chmod')
      ->will($this->returnValue(FALSE));

    $this->assertFalse($image->save());
  }

  /**
   * Tests \Drupal\Core\Image\Image::save().
   */
  public function testProcessInfoFails() {
    $this->image->setSource('magic-foobars.png');
    $this->assertFalse((bool) $this->image->getWidth());
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleWidth() {
    $this->toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnArgument(2));
    $height = $this->image->scale(44);
    $this->assertEquals($height, 50);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleHeight() {
    $this->toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnArgument(1));

    $width = $this->image->scale(NULL, 50);
    $this->assertEquals($width, 44);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scale().
   */
  public function testScaleSame() {
    // Dimensions are the same, resize should not be called.
    $this->toolkit->expects($this->never())
      ->method('resize')
      ->will($this->returnArgument(1));

    $width = $this->image->scale(88, 100);
    $this->assertEquals($width, 88);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropWidth() {
    $this->toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnValue(TRUE));

    $this->toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(1));

    $x = $this->image->scaleAndCrop(34, 50);
    $this->assertEquals($x, 5);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropHeight() {
    $this->toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnValue(TRUE));

    $this->toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(2));

    $y = $this->image->scaleAndCrop(44, 40);
    $this->assertEquals($y, 5);
  }

  /**
   * Tests \Drupal\Core\Image\Image::scaleAndCrop().
   */
  public function testScaleAndCropFails() {
    $this->toolkit->expects($this->once())
      ->method('resize')
      ->will($this->returnValue(FALSE));

    $this->toolkit->expects($this->never())
      ->method('crop');
    $this->image->scaleAndCrop(44, 40);
  }

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   */
  public function testCropWidth() {
    $this->toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(4));
    // Cropping with width only should preserve the aspect ratio.
    $height = $this->image->crop(0, 0, 44, NULL);
    $this->assertEquals($height, 50);
  }

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   */
  public function testCropHeight() {
    $this->toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(3));
    // Cropping with height only should preserve the aspect ratio.
    $width = $this->image->crop(0, 0, NULL, 50);
    $this->assertEquals($width, 44);
  }

  /**
   * Tests \Drupal\Core\Image\Image::crop().
   */
  public function testCrop() {
    $this->toolkit->expects($this->once())
      ->method('crop')
      ->will($this->returnArgument(3));
    $width = $this->image->crop(0, 0, 44, 50);
    $this->assertEquals($width, 44);
  }

  /**
   * Tests \Drupal\Core\Image\Image::resize().
   */
  public function testResize() {
    $this->toolkit->expects($this->exactly(2))
      ->method('resize')
      ->will($this->returnArgument(1));
    // Resize with integer for width and height.
    $this->image->resize(30, 40);
    // Pass a float for width.
    $width = $this->image->resize(30.4, 40);
    // Ensure that the float was rounded to an integer first.
    $this->assertEquals($width, 30);
  }

  /**
   * Tests \Drupal\Core\Image\Image::desaturate().
   */
  public function testDesaturate() {
    $this->toolkit->expects($this->once())
      ->method('desaturate');
    $this->image->desaturate();
  }

  /**
   * Tests \Drupal\Core\Image\Image::rotate().
   */
  public function testRotate() {
    $this->toolkit->expects($this->once())
      ->method('rotate');
    $this->image->rotate(90);
  }

}
