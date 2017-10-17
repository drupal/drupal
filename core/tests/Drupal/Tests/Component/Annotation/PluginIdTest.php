<?php

namespace Drupal\Tests\Component\Annotation;

use Drupal\Component\Annotation\PluginID;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\PluginId
 * @group Annotation
 */
class PluginIdTest extends TestCase {

  /**
   * @covers ::get
   */
  public function testGet() {
    // Assert plugin starts empty regardless of constructor.
    $plugin = new PluginID([
      'foo' => 'bar',
      'biz' => [
        'baz' => 'boom',
      ],
      'nestedAnnotation' => new PluginID([
        'foo' => 'bar',
      ]),
      'value' => 'biz',
    ]);
    $this->assertEquals([
      'id' => NULL,
      'class' => NULL,
      'provider' => NULL,
    ], $plugin->get());

    // Set values and ensure we can retrieve them.
    $plugin->value = 'foo';
    $plugin->setClass('bar');
    $plugin->setProvider('baz');
    $this->assertEquals([
      'id' => 'foo',
      'class' => 'bar',
      'provider' => 'baz',
    ], $plugin->get());
  }

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $plugin = new PluginID([]);
    $plugin->value = 'example';
    $this->assertEquals('example', $plugin->getId());
  }

}
