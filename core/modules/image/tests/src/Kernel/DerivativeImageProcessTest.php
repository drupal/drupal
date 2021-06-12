<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\image\Entity\ImageStyle;

/**
 * @coversDefaultClass \Drupal\image\Plugin\ImageProcessPipeline\Derivative
 *
 * @group image
 */
class DerivativeImageProcessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'image', 'image_module_test'];

  /**
   * The image processor service.
   *
   * @var \Drupal\image\ImageProcessor
   */
  protected $imageProcessor;

  /**
   * An image style for testing.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $imageStyle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'image']);
    $this->imageProcessor = \Drupal::service('image.processor');
    $this->imageStyle = ImageStyle::load('thumbnail');
    \Drupal::service('file_system')->copy('core/tests/fixtures/files/image-1.png', 'public://test.png');
  }

  /**
   * @covers ::setImageStyle
   * @covers ::setSourceImageUri
   * @covers ::isSourceImageProcessable
   */
  public function testIsSourceImageProcessable() {
    // Starting off from a valid image file.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('public://test.png');
    $this->assertTrue($pipeline->isSourceImageProcessable());

    // Starting off from non-image file.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('public://test.csv');
    $this->assertFalse($pipeline->isSourceImageProcessable());
  }

  /**
   * @covers ::setImageStyle
   * @covers ::setSourceImageUri
   * @covers ::setSourceImageFileExtension
   * @covers ::getDerivativeImageFileExtension
   */
  public function testGetDerivativeImageFileExtension() {
    // Starting off from a real source.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('public://test.png');
    $this->assertSame('png', $pipeline->getDerivativeImageFileExtension());

    // Starting off from the image file extension.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageFileExtension('jpg');
    $this->assertSame('jpg', $pipeline->getDerivativeImageFileExtension());
  }

  /**
   * @covers ::setImageStyle
   * @covers ::setSourceImageUri
   * @covers ::setSourceImageDimensions
   * @covers ::getDerivativeImageWidth
   * @covers ::getDerivativeImageHeight
   */
  public function testGetDerivativeImageDimensions() {
    // Starting off from a real source.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('public://test.png')
      ->setSourceImageDimensions(360, 240);
    $this->assertSame(100, $pipeline->getDerivativeImageWidth());
    $this->assertSame(67, $pipeline->getDerivativeImageHeight());

    // Starting off from a non-existent source, only dimensions.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('')
      ->setSourceImageDimensions(100, 200);
    $this->assertSame(50, $pipeline->getDerivativeImageWidth());
    $this->assertSame(100, $pipeline->getDerivativeImageHeight());
  }

  /**
   * @covers ::setImageStyle
   * @covers ::setSourceImageUri
   * @covers ::getDerivativeImageUri
   */
  public function testGetDerivativeImageUri() {
    // Starting off from an URI.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('public://test.png');
    $this->assertEquals('public://styles/thumbnail/public/test.png', $pipeline->getDerivativeImageUri());

    // Starting off from a path.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('core/modules/image/sample.png');
    $this->assertEquals('public://styles/thumbnail/public/core/modules/image/sample.png', $pipeline->getDerivativeImageUri());
  }

  /**
   * @covers ::setImageStyle
   * @covers ::setSourceImageUri
   * @covers ::buildDerivativeImage
   */
  public function testBuildDerivativeImageFromFile() {
    // Starting off from an URI.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('public://test.png');
    $this->assertTrue($pipeline->buildDerivativeImage());
    $this->assertFileExists('public://styles/thumbnail/public/test.png');
    $image = \Drupal::service('image.factory')->get('public://styles/thumbnail/public/test.png');
    $this->assertSame(100, $image->getWidth());
    $this->assertSame(67, $image->getHeight());

    // Starting off from an URI, file missing.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('public://missing.png');
    $this->assertFalse($pipeline->buildDerivativeImage());
    $this->assertFileNotExists('public://styles/thumbnail/public/missing.png');

    // Starting off from a path.
    $pipeline = $this->imageProcessor->createInstance('derivative')
      ->setImageStyle($this->imageStyle)
      ->setSourceImageUri('core/modules/image/sample.png');
    $this->assertTrue($pipeline->buildDerivativeImage());
    $this->assertFileExists('public://styles/thumbnail/public/core/modules/image/sample.png');
    $image = \Drupal::service('image.factory')->get('public://styles/thumbnail/public/core/modules/image/sample.png');
    $this->assertSame(100, $image->getWidth());
    $this->assertSame(75, $image->getHeight());
  }

  /**
   * Tests creating a derivative straight from an Image object.
   *
   * @covers ::setImageStyle
   * @covers ::setImage
   * @covers ::setSourceImageFileExtension
   * @covers ::setDerivativeImageUri
   * @covers ::buildDerivativeImage
   */
  public function testBuildDerivativeImageFromImageObject() {
    // Create scratch image.
    $image = \Drupal::service('image.factory')->get();
    $this->assertSame('', $image->getSource());
    $this->assertSame('', $image->getMimeType());
    $this->assertNull($image->getFileSize());
    $image->createNew(600, 450, 'png');
    $this->assertSame('', $image->getSource());
    $this->assertSame('image/png', $image->getMimeType());
    $this->assertNull($image->getFileSize());

    // Create derivative.
    $pipeline = $this->imageProcessor->createInstance('derivative');
    $derivative_uri = 'public://test_0.png';
    $pipeline
      ->setImageStyle($this->imageStyle)
      ->setImage($image)
      ->setSourceImageFileExtension('png')
      ->setDerivativeImageUri($derivative_uri);
    $this->assertTrue($pipeline->buildDerivativeImage());

    // Check if derivative image exists.
    $this->assertFileExists($derivative_uri);

    // Check derivative image after saving, with old object.
    $this->assertSame(100, $image->getWidth());
    $this->assertSame(75, $image->getHeight());
    $this->assertSame($derivative_uri, $image->getSource());
    $this->assertSame('image/png', $image->getMimeType());
    $file_size = $image->getFileSize();
    $this->assertGreaterThan(0, $file_size);

    // Check derivative image after reloading from saved image file.
    $image_r = \Drupal::service('image.factory')->get($derivative_uri);
    $this->assertSame(100, $image_r->getWidth());
    $this->assertSame(75, $image_r->getHeight());
    $this->assertSame($derivative_uri, $image_r->getSource());
    $this->assertSame('image/png', $image_r->getMimeType());
    $this->assertSame($file_size, $image_r->getFileSize());
  }

}
