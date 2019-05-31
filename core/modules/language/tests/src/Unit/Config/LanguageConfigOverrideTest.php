<?php

namespace Drupal\Tests\language\Unit\Config;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\language\Config\LanguageConfigOverride;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\language\Config\LanguageConfigOverride
 * @group Config
 * @group language
 */
class LanguageConfigOverrideTest extends UnitTestCase {

  /**
   * Language configuration override.
   *
   * @var \Drupal\language\Config\LanguageConfigOverride
   */
  protected $configTranslation;

  /**
   * Storage.
   *
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * Typed Config.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfig;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->storage = $this->createMock('Drupal\Core\Config\StorageInterface');
    $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->typedConfig = $this->createMock('\Drupal\Core\Config\TypedConfigManagerInterface');
    $this->configTranslation = new LanguageConfigOverride('config.test', $this->storage, $this->typedConfig, $this->eventDispatcher);
    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $container = new ContainerBuilder();
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::save
   */
  public function testSaveNew() {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['config:config.test']);
    $this->assertTrue($this->configTranslation->isNew());
    $this->configTranslation->save();
  }

  /**
   * @covers ::save
   */
  public function testSaveExisting() {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['config:config.test']);
    $this->configTranslation->initWithData([]);
    $this->configTranslation->save();
  }

  /**
   * @covers ::delete
   */
  public function testDelete() {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['config:config.test']);
    $this->configTranslation->initWithData([]);
    $this->configTranslation->delete();
  }

}
