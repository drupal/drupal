<?php

namespace Drupal\Tests\image\Unit;

use Drupal\Component\Utility\Crypt;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\image\Entity\ImageStyle
 *
 * @group Image
 */
class ImageStyleTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Gets a mocked image style for testing.
   *
   * @param string $image_effect_id
   *   The image effect ID.
   * @param \Drupal\image\ImageEffectInterface|\PHPUnit\Framework\MockObject\MockObject $image_effect
   *   The image effect used for testing.
   * @param array $stubs
   *   An array of additional method names to mock.
   *
   * @return \Drupal\image\ImageStyleInterface
   *   The mocked image style.
   */
  protected function getImageStyleMock($image_effect_id, $image_effect, $stubs = []) {
    $effectManager = $this->getMockBuilder('\Drupal\image\ImageEffectManager')
      ->disableOriginalConstructor()
      ->getMock();
    $effectManager->expects($this->any())
      ->method('createInstance')
      ->with($image_effect_id)
      ->will($this->returnValue($image_effect));
    $default_stubs = [
      'getImageEffectPluginManager',
      'fileUriScheme',
      'fileUriTarget',
      'fileDefaultScheme',
    ];
    $image_style = $this->getMockBuilder('\Drupal\image\Entity\ImageStyle')
      ->setConstructorArgs([
        ['effects' => [$image_effect_id => ['id' => $image_effect_id]]],
        $this->entityTypeId,
      ])
      ->setMethods(array_merge($default_stubs, $stubs))
      ->getMock();

    $image_style->expects($this->any())
      ->method('getImageEffectPluginManager')
      ->will($this->returnValue($effectManager));
    $image_style->expects($this->any())
      ->method('fileDefaultScheme')
      ->will($this->returnCallback([$this, 'fileDefaultScheme']));

    return $image_style;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = $this->randomMachineName();
    $this->provider = $this->randomMachineName();
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue($this->provider));
    $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));
  }

  /**
   * @covers ::getDerivativeExtension
   */
  public function testGetDerivativeExtension() {
    $image_effect_id = $this->randomMachineName();
    $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->will($this->returnValue('png'));

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect);

    $extensions = ['jpeg', 'gif', 'png'];
    foreach ($extensions as $extension) {
      $extensionReturned = $image_style->getDerivativeExtension($extension);
      $this->assertEquals('png', $extensionReturned);
    }
  }

  /**
   * @covers ::buildUri
   */
  public function testBuildUri() {
    // Image style that changes the extension.
    $image_effect_id = $this->randomMachineName();
    $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->will($this->returnValue('png'));

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect);
    $this->assertEquals($image_style->buildUri('public://test.jpeg'), 'public://styles/' . $image_style->id() . '/public/test.jpeg.png');

    // Image style that doesn't change the extension.
    $image_effect_id = $this->randomMachineName();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->will($this->returnArgument(0));

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect);
    $this->assertEquals($image_style->buildUri('public://test.jpeg'), 'public://styles/' . $image_style->id() . '/public/test.jpeg');
  }

  /**
   * @covers ::getPathToken
   */
  public function testGetPathToken() {
    $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
    $private_key = $this->randomMachineName();
    $hash_salt = $this->randomMachineName();

    // Image style that changes the extension.
    $image_effect_id = $this->randomMachineName();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->will($this->returnValue('png'));

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect, ['getPrivateKey', 'getHashSalt']);
    $image_style->expects($this->any())
      ->method('getPrivateKey')
      ->will($this->returnValue($private_key));
    $image_style->expects($this->any())
      ->method('getHashSalt')
      ->will($this->returnValue($hash_salt));

    // Assert the extension has been added to the URI before creating the token.
    $this->assertEquals($image_style->getPathToken('public://test.jpeg.png'), $image_style->getPathToken('public://test.jpeg'));
    $this->assertEquals(substr(Crypt::hmacBase64($image_style->id() . ':' . 'public://test.jpeg.png', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));
    $this->assertNotEquals(substr(Crypt::hmacBase64($image_style->id() . ':' . 'public://test.jpeg', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));

    // Image style that doesn't change the extension.
    $image_effect_id = $this->randomMachineName();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->will($this->returnArgument(0));

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect, ['getPrivateKey', 'getHashSalt']);
    $image_style->expects($this->any())
      ->method('getPrivateKey')
      ->will($this->returnValue($private_key));
    $image_style->expects($this->any())
      ->method('getHashSalt')
      ->will($this->returnValue($hash_salt));
    // Assert no extension has been added to the uri before creating the token.
    $this->assertNotEquals($image_style->getPathToken('public://test.jpeg.png'), $image_style->getPathToken('public://test.jpeg'));
    $this->assertNotEquals(substr(Crypt::hmacBase64($image_style->id() . ':' . 'public://test.jpeg.png', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));
    $this->assertEquals(substr(Crypt::hmacBase64($image_style->id() . ':' . 'public://test.jpeg', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));
  }

  /**
   * Mock function for ImageStyle::fileDefaultScheme().
   */
  public function fileDefaultScheme() {
    return 'public';
  }

}
