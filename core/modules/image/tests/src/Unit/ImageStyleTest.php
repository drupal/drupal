<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Unit;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\ContainerBuilder;
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
      ->willReturn($image_effect);
    $default_stubs = ['getImageEffectPluginManager', 'fileDefaultScheme'];
    $image_style = $this->getMockBuilder('\Drupal\image\Entity\ImageStyle')
      ->setConstructorArgs([
        ['effects' => [$image_effect_id => ['id' => $image_effect_id]]],
        $this->entityTypeId,
      ])
      ->onlyMethods(array_merge($default_stubs, $stubs))
      ->getMock();

    $image_style->expects($this->any())
      ->method('getImageEffectPluginManager')
      ->willReturn($effectManager);
    $image_style->expects($this->any())
      ->method('fileDefaultScheme')
      ->willReturnCallback([$this, 'fileDefaultScheme']);

    return $image_style;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeId = $this->randomMachineName();
    $provider = $this->randomMachineName();
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->willReturn($provider);
    $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
  }

  /**
   * @covers ::getDerivativeExtension
   */
  public function testGetDerivativeExtension(): void {
    $image_effect_id = $this->randomMachineName();
    $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->willReturn('png');

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
  public function testBuildUri(): void {
    // Image style that changes the extension.
    $image_effect_id = $this->randomMachineName();
    $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->willReturn('png');

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect);
    $this->assertEquals($image_style->buildUri('public://test.jpeg'), 'public://styles/' . $image_style->id() . '/public/test.jpeg.png');

    // Image style that doesn't change the extension.
    $image_effect_id = $this->randomMachineName();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->willReturnArgument(0);

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect);
    $this->assertEquals($image_style->buildUri('public://test.jpeg'), 'public://styles/' . $image_style->id() . '/public/test.jpeg');
  }

  /**
   * @covers ::getPathToken
   */
  public function testGetPathToken(): void {
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
      ->willReturn('png');

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect, ['getPrivateKey', 'getHashSalt']);
    $image_style->expects($this->any())
      ->method('getPrivateKey')
      ->willReturn($private_key);
    $image_style->expects($this->any())
      ->method('getHashSalt')
      ->willReturn($hash_salt);

    // Assert the extension has been added to the URI before creating the token.
    $this->assertEquals($image_style->getPathToken('public://test.jpeg.png'), $image_style->getPathToken('public://test.jpeg'));
    $this->assertEquals(substr(Crypt::hmacBase64($image_style->id() . ':public://test.jpeg.png', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));
    $this->assertNotEquals(substr(Crypt::hmacBase64($image_style->id() . ':public://test.jpeg', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));

    // Image style that doesn't change the extension.
    $image_effect_id = $this->randomMachineName();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase')
      ->setConstructorArgs([[], $image_effect_id, [], $logger])
      ->getMock();
    $image_effect->expects($this->any())
      ->method('getDerivativeExtension')
      ->willReturnArgument(0);

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect, ['getPrivateKey', 'getHashSalt']);
    $image_style->expects($this->any())
      ->method('getPrivateKey')
      ->willReturn($private_key);
    $image_style->expects($this->any())
      ->method('getHashSalt')
      ->willReturn($hash_salt);
    // Assert no extension has been added to the uri before creating the token.
    $this->assertNotEquals($image_style->getPathToken('public://test.jpeg.png'), $image_style->getPathToken('public://test.jpeg'));
    $this->assertNotEquals(substr(Crypt::hmacBase64($image_style->id() . ':public://test.jpeg.png', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));
    $this->assertEquals(substr(Crypt::hmacBase64($image_style->id() . ':public://test.jpeg', $private_key . $hash_salt), 0, 8), $image_style->getPathToken('public://test.jpeg'));
  }

  /**
   * @covers ::flush
   */
  public function testFlush(): void {
    $cache_tag_invalidator = $this->createMock('\Drupal\Core\Cache\CacheTagsInvalidator');
    $file_system = $this->createMock('\Drupal\Core\File\FileSystemInterface');
    $module_handler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $stream_wrapper_manager = $this->createMock('\Drupal\Core\StreamWrapper\StreamWrapperManagerInterface');
    $stream_wrapper_manager->expects($this->any())
      ->method('getWrappers')
      ->willReturn([]);
    $theme_registry = $this->createMock('\Drupal\Core\Theme\Registry');

    $container = new ContainerBuilder();
    $container->set('cache_tags.invalidator', $cache_tag_invalidator);
    $container->set('file_system', $file_system);
    $container->set('module_handler', $module_handler);
    $container->set('stream_wrapper_manager', $stream_wrapper_manager);
    $container->set('theme.registry', $theme_registry);
    \Drupal::setContainer($container);

    $image_effect_id = $this->randomMachineName();
    $image_effect = $this->getMockBuilder('\Drupal\image\ImageEffectBase');

    $image_style = $this->getImageStyleMock($image_effect_id, $image_effect, ['buildUri', 'getCacheTagsToInvalidate']);
    $image_style->expects($this->any())
      ->method('buildUri')
      ->willReturn('test.jpg');
    $image_style->expects($this->any())
      ->method('getCacheTagsToInvalidate')
      ->willReturn([]);

    // Assert the theme registry is reset.
    $theme_registry
      ->expects($this->once())
      ->method('reset');
    // Assert the cache tags are invalidated.
    $cache_tag_invalidator
      ->expects($this->once())
      ->method('invalidateTags');

    $image_style->flush();

    // Assert the theme registry is not reset a path is flushed.
    $theme_registry
      ->expects($this->never())
      ->method('reset');
    // Assert the cache tags are not reset a path is flushed.
    $cache_tag_invalidator
      ->expects($this->never())
      ->method('invalidateTags');

    $image_style->flush('test.jpg');

  }

  /**
   * Mock function for ImageStyle::fileDefaultScheme().
   */
  public function fileDefaultScheme() {
    return 'public';
  }

}
