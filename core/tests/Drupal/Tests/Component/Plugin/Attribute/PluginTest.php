<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Plugin
 * @group Attribute
 */
class PluginTest extends TestCase {

  /**
   * @covers ::__construct
   * @covers ::get
   */
  public function testGet(): void {
    $plugin = new PluginStub(id: 'example', deriver: 'test');
    $plugin->setClass('foo');
    $this->assertEquals([
      'id' => 'example',
      'class' => 'foo',
      'deriver' => 'test',
    ], $plugin->get());
  }

  /**
   * @covers ::setProvider
   * @covers ::getProvider
   */
  public function testSetProvider(): void {
    $plugin = new Plugin(id: 'example');
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * @covers ::getId
   */
  public function testGetId(): void {
    $plugin = new Plugin(id: 'example');
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @covers ::setClass
   * @covers ::getClass
   */
  public function testSetClass(): void {
    $plugin = new Plugin(id: 'test');
    $plugin->setClass('example');
    $this->assertEquals('example', $plugin->getClass());
  }

}

/**
 * {@inheritdoc}
 */
class PluginStub extends Plugin {

}
