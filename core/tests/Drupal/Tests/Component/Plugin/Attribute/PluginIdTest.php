<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\PluginID;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Plugin\Attribute\PluginID.
 */
#[CoversClass(PluginID::class)]
#[Group('Attribute')]
class PluginIdTest extends TestCase {

  /**
   * @legacy-covers ::get
   */
  public function testGet(): void {
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
