<?php

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @group Config
 * @coversDefaultClass \Drupal\Core\Config\ConfigFactory
 */
class ConfigFactoryTest extends UnitTestCase {

  /**
   * Config factory under test.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

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
    $this->configFactory = new ConfigFactory($this->storage, $this->eventDispatcher, $this->typedConfig);

    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $container = new ContainerBuilder();
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::rename
   */
  public function testRename() {
    $old = new Config($this->randomMachineName(), $this->storage, $this->eventDispatcher, $this->typedConfig);
    $new = new Config($this->randomMachineName(), $this->storage, $this->eventDispatcher, $this->typedConfig);

    $this->storage->expects($this->exactly(2))
      ->method('readMultiple')
      ->willReturnMap([
        [[$old->getName()], $old->getRawData()],
        [[$new->getName()], $new->getRawData()],
      ]);

    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($old->getCacheTags());

    $this->storage->expects($this->once())
      ->method('rename')
      ->with($old->getName(), $new->getName());

    $this->configFactory->rename($old->getName(), $new->getName());
  }

}
