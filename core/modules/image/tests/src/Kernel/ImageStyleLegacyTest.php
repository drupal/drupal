<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\image\Entity\ImageStyle;

/**
 * Legacy test for deprecated ImageStyle methods.
 *
 * @coversDefaultClass \Drupal\image\Entity\ImageStyle
 *
 * @group image
 * @group legacy
 */
class ImageStyleLegacyTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'image', 'image_module_test'];

  /**
   * An image style for testing.
   *
   * @var \Drupal\image\ImageStyleInterface
   *   The mocked image style.
   */
  protected $imageStyle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->imageStyle = ImageStyle::create([
      'name' => 'test',
    ]);
    $this->imageStyle->addImageEffect(['id' => 'image_module_test_null']);
    $this->imageStyle->save();

    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://test.png');
  }

  /**
   * @covers ::buildUri
   */
  public function testBuildUri() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::buildUri method is deprecated since version 9.x.x and will be removed in y.y.y.');
    $this->assertSame('public://styles/test/public/test.png', $this->imageStyle->buildUri('public://test.png'));
  }

  /**
   * @covers ::buildUrl
   */
  public function testBuildUrl() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::buildUrl method is deprecated since version 9.x.x and will be removed in y.y.y.');
    $this->assertStringContainsString('files/styles/test/public/test.png?itok=', $this->imageStyle->buildUrl('public://test.png'));
  }

  /**
   * @covers ::createDerivative
   */
  public function testCreateDerivative() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::createDerivative method is deprecated since version 9.x.x and will be removed in y.y.y.');
    $this->assertIsBool($this->imageStyle->createDerivative('public://test.png', 'public://test_derivative.png'));
  }

  /**
   * @covers ::transformDimensions
   */
  public function testTransformDimensions() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::transformDimensions method is deprecated since version 9.x.x and will be removed in y.y.y.');
    $dimensions = ['width' => 100, 'height' => 200];
    $this->assertNull($this->imageStyle->transformDimensions($dimensions, 'public://test.png'));
    $this->assertEquals([
      'width' => NULL,
      'height' => NULL,
    ], $dimensions);
  }

  /**
   * @covers ::getDerivativeExtension
   */
  public function testGetDerivativeExtension() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::getDerivativeExtension method is deprecated since version 9.x.x and will be removed in y.y.y.');
    $this->assertSame('png', $this->imageStyle->getDerivativeExtension('png'));
  }

  /**
   * @covers ::getPathToken
   */
  public function testGetPathToken() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::getPathToken method is deprecated since version 9.x.x and will be removed in y.y.y.');
    $this->assertNotEmpty($this->imageStyle->getPathToken('public://test.png'));
  }

  /**
   * @covers ::supportsUri
   */
  public function testSupportsUri() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::supportsUri method is deprecated since version 9.x.x and will be removed in y.y.y.');
    $this->assertTrue($this->imageStyle->supportsUri('public://test.png'));
  }

}
