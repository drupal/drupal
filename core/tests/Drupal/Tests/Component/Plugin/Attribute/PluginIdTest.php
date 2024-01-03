<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\Attribute\PluginId
 * @group Attribute
 */
class PluginIdTest extends TestCase {

  /**
   * @covers ::get
   */
  public function testGet() {
    // Assert plugin starts with only an ID.
    $plugin = new PluginID(id: 'test');
    // Plugin's always have a class set by discovery.
    $plugin->setClass('bar');
    $this->assertEquals([
      'id' => 'test',
      'class' => 'bar',
      'provider' => NULL,
    ], $plugin->get());

    // Set values and ensure we can retrieve them.
    $plugin->setClass('bar2');
    $plugin->setProvider('baz');
    $this->assertEquals([
      'id' => 'test',
      'class' => 'bar2',
      'provider' => 'baz',
    ], $plugin->get());
  }

}
