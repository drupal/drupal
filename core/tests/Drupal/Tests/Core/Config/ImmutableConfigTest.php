<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ImmutableConfigException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests Drupal\Core\Config\ImmutableConfig.
 */
#[CoversClass(ImmutableConfig::class)]
#[Group('Config')]
class ImmutableConfigTest extends UnitTestCase {

  /**
   * The immutable config object under test.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $storage = $this->createStub(StorageInterface::class);
    $event_dispatcher = $this->createStub(EventDispatcherInterface::class);
    $typed_config = $this->createStub(TypedConfigManagerInterface::class);
    $this->config = new ImmutableConfig('test', $storage, $event_dispatcher, $typed_config);
  }

  /**
   * Tests set.
   */
  public function testSet(): void {
    $this->expectException(ImmutableConfigException::class);
    $this->expectExceptionMessageIs('Can not set values on immutable configuration test:name. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->set('name', 'value');
  }

  /**
   * Tests clear.
   */
  public function testClear(): void {
    $this->expectException(ImmutableConfigException::class);
    $this->expectExceptionMessageIs('Can not clear name key in immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->clear('name');
  }

  /**
   * Tests save.
   */
  public function testSave(): void {
    $this->expectException(ImmutableConfigException::class);
    $this->expectExceptionMessageIs('Can not save immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->save();
  }

  /**
   * Tests delete.
   */
  public function testDelete(): void {
    $this->expectException(ImmutableConfigException::class);
    $this->expectExceptionMessageIs('Can not delete immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->delete();
  }

}
