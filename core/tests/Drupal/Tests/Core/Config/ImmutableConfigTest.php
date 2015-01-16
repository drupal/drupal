<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\ImmutableConfigTest.
 */

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\ImmutableConfig;
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
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $typed_config = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');
    $this->config = new ImmutableConfig('test', $storage, $event_dispatcher, $typed_config);
  }

  /**
   * @covers ::set
   * @expectedException \Drupal\Core\Config\ImmutableConfigException
   * @expectedExceptionMessage Can not set values on immutable configuration test:name. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object
   */
  public function testSet() {
    $this->config->set('name', 'value');
  }

  /**
   * @covers ::clear
   * @expectedException \Drupal\Core\Config\ImmutableConfigException
   * @expectedExceptionMessage Can not clear name key in immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object
   */
  public function testClear() {
    $this->config->clear('name');
  }

  /**
   * @covers ::save
   * @expectedException \Drupal\Core\Config\ImmutableConfigException
   * @expectedExceptionMessage Can not save immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object
   */
  public function testSave() {
    $this->config->save();
  }

  /**
   * @covers ::delete
   * @expectedException \Drupal\Core\Config\ImmutableConfigException
   * @expectedExceptionMessage Can not delete immutable configuration test. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object
   */
  public function testDelete() {
    $this->config->delete();
  }

}
