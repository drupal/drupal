<?php

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ImmutableConfigException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\ImmutableConfig
 * @group Config
 */
class ImmutableConfigTest extends UnitTestCase {

  /**
   * The immutable config object under test.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  protected function setUp() {
    parent::setUp();
    $storage = $this->createMock('Drupal\Core\Config\StorageInterface');
    $event_dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $typed_config = $this->createMock('Drupal\Core\Config\TypedConfigManagerInterface');
    $this->config = new ImmutableConfig('test', $storage, $event_dispatcher, $typed_config);
  }

  /**
   * @covers ::set
   */
  public function testSet() {
    $this->setExpectedException(ImmutableConfigException::class, 'Can not set values on immutable configuration test:name. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->set('name', 'value');
  }

  /**
   * @covers ::clear
   */
  public function testClear() {
    $this->setExpectedException(ImmutableConfigException::class, 'Can not clear name key in immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->clear('name');
  }

  /**
   * @covers ::save
   */
  public function testSave() {
    $this->setExpectedException(ImmutableConfigException::class, 'Can not save immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->save();
  }

  /**
   * @covers ::delete
   */
  public function testDelete() {
    $this->setExpectedException(ImmutableConfigException::class, 'Can not delete immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object');
    $this->config->delete();
  }

}
