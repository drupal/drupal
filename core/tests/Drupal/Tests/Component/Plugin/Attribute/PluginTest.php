<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Plugin\Attribute\Plugin.
 */
#[CoversClass(Plugin::class)]
#[Group('Attribute')]
class PluginTest extends TestCase {

  /**
   * @legacy-covers ::__construct
   * @legacy-covers ::get
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
   * @legacy-covers ::setProvider
   * @legacy-covers ::getProvider
   */
  public function testSetProvider(): void {
    $plugin = new Plugin(id: 'example');
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
  }

  /**
   * @legacy-covers ::getId
   */
  public function testGetId(): void {
    $plugin = new Plugin(id: 'example');
    $this->assertEquals('example', $plugin->getId());
  }

  /**
   * @legacy-covers ::setClass
   * @legacy-covers ::getClass
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
